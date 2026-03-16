<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/db.php';

function json_response(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

function pdf_escape(string $text): string
{
    $text = str_replace('\\', '\\\\', $text);
    $text = str_replace('(', '\\(', $text);
    $text = str_replace(')', '\\)', $text);
    return $text;
}

function build_simple_pdf(string $title, array $lines): string
{
    $yStart = 760;
    $lineHeight = 14;

    $content = "BT\n/F1 16 Tf\n72 $yStart Td\n(" . pdf_escape($title) . ") Tj\nET\n";

    $y = $yStart - 24;
    $content .= "BT\n/F1 11 Tf\n";
    foreach ($lines as $line) {
        if ($y < 60) break;
        $content .= "72 $y Td\n(" . pdf_escape((string)$line) . ") Tj\n";
        $y -= $lineHeight;
    }
    $content .= "ET\n";

    $objects = [];
    $objects[] = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
    $objects[] = "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
    $objects[] = "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>\nendobj\n";
    $objects[] = "4 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";
    $objects[] = "5 0 obj\n<< /Length " . strlen($content) . " >>\nstream\n" . $content . "endstream\nendobj\n";

    $pdf = "%PDF-1.4\n";
    $offsets = [0];
    foreach ($objects as $obj) {
        $offsets[] = strlen($pdf);
        $pdf .= $obj;
    }

    $xrefPos = strlen($pdf);
    $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
    $pdf .= "0000000000 65535 f \n";
    for ($i = 1; $i <= count($objects); $i++) {
        $pdf .= str_pad((string)$offsets[$i], 10, '0', STR_PAD_LEFT) . " 00000 n \n";
    }

    $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\n";
    $pdf .= "startxref\n" . $xrefPos . "\n%%EOF";

    return $pdf;
}

function build_mail_with_attachment(string $to, string $subject, string $bodyText, string $filename, string $fileContent, string $from): array
{
    $boundary = 'bnd_' . bin2hex(random_bytes(12));

    $headers = [];
    $headers[] = 'From: ' . $from;
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-Type: multipart/mixed; boundary="' . $boundary . '"';

    $body = "--$boundary\r\n";
    $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
    $body .= $bodyText . "\r\n\r\n";

    $body .= "--$boundary\r\n";
    $body .= "Content-Type: application/pdf; name=\"$filename\"\r\n";
    $body .= "Content-Transfer-Encoding: base64\r\n";
    $body .= "Content-Disposition: attachment; filename=\"$filename\"\r\n\r\n";
    $body .= chunk_split(base64_encode($fileContent)) . "\r\n";
    $body .= "--$boundary--\r\n";

    return [$headers, $body];
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_response(405, ['status' => 'error', 'message' => 'Method not allowed.']);
    }

    $input = json_decode((string)file_get_contents('php://input'), true);
    if (!is_array($input)) {
        json_response(400, ['status' => 'error', 'message' => 'Invalid JSON payload.']);
    }

    $teacherId = isset($input['teacher_id']) ? (int)$input['teacher_id'] : 0;
    if ($teacherId <= 0) {
        json_response(400, ['status' => 'error', 'message' => 'teacher_id is required.']);
    }

    $tStmt = $pdo->prepare('SELECT id, name, email, type, current_units, max_units FROM teachers WHERE id = ?');
    $tStmt->execute([$teacherId]);
    $teacher = $tStmt->fetch(PDO::FETCH_ASSOC);
    if (!$teacher) {
        json_response(404, ['status' => 'error', 'message' => 'Teacher not found.']);
    }

    $to = trim((string)($teacher['email'] ?? ''));
    if ($to === '') {
        json_response(400, ['status' => 'error', 'message' => 'Teacher email is missing.']);
    }

    $aStmt = $pdo->prepare(
        'SELECT sub.course_code, sub.name AS subject_name, sub.units AS subject_units
         FROM assignments a
         JOIN subjects sub ON a.subject_id = sub.id
         WHERE a.teacher_id = ?
         ORDER BY sub.course_code ASC'
    );
    $aStmt->execute([$teacherId]);
    $subjects = $aStmt->fetchAll(PDO::FETCH_ASSOC);

    $title = 'SmartLoad Teacher Load Report';
    $lines = [];
    $lines[] = 'Teacher: ' . (string)$teacher['name'];
    $lines[] = 'Type: ' . (string)$teacher['type'];
    $lines[] = 'Load: ' . (int)$teacher['current_units'] . ' / ' . (int)$teacher['max_units'] . ' units';
    $lines[] = '';
    $lines[] = 'Assigned Subjects:';

    if (empty($subjects)) {
        $lines[] = '  (none)';
    } else {
        foreach ($subjects as $s) {
            $lines[] = '  - ' . $s['course_code'] . ' - ' . $s['subject_name'] . ' (' . (int)$s['subject_units'] . ' units)';
        }
    }

    $pdf = build_simple_pdf($title, $lines);

    $filename = 'teacher_load_' . (int)$teacherId . '.pdf';
    $subject = 'SmartLoad: Your Teaching Load Report';
    $bodyText = "Hello " . $teacher['name'] . ",\n\nAttached is your SmartLoad teaching load report.\n\n- SmartLoad";

    $from = 'SmartLoad <noreply@localhost>';
    [$headers, $body] = build_mail_with_attachment($to, $subject, $bodyText, $filename, $pdf, $from);

    $ok = @mail($to, $subject, $body, implode("\r\n", $headers));
    if (!$ok) {
        json_response(500, [
            'status' => 'error',
            'message' => 'Failed to send email. PHP mail() is likely not configured on this server.',
        ]);
    }

    json_response(200, ['status' => 'success']);

} catch (Exception $e) {
    json_response(500, ['status' => 'error', 'message' => 'Failed to send PDF: ' . $e->getMessage()]);
}
