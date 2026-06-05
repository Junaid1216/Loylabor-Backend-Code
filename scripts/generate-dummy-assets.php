<?php

$dummyDir = __DIR__ . '/../public/dummy';

if (!is_dir($dummyDir)) {
    mkdir($dummyDir, 0755, true);
}

$sourceCnic = dirname(__DIR__) . '/assets/c__Users_Lenovo_AppData_Roaming_Cursor_User_workspaceStorage_b01687a56423ee8aba6fd44d311341c5_images_image-fa520643-0a06-410b-b05a-169e75229af5.png';
if (!file_exists($sourceCnic)) {
    $sourceCnic = 'C:/Users/Lenovo/.cursor/projects/c-xampp-htdocs-homeservices-12Mar2026/assets/c__Users_Lenovo_AppData_Roaming_Cursor_User_workspaceStorage_b01687a56423ee8aba6fd44d311341c5_images_image-fa520643-0a06-410b-b05a-169e75229af5.png';
}

if (file_exists($sourceCnic)) {
    copy($sourceCnic, $dummyDir . '/cnic-front.jpg');
    copy($sourceCnic, $dummyDir . '/cnic-back.jpg');
    copy($sourceCnic, $dummyDir . '/technician-photo.jpg');
    echo "CNIC dummy images copied.\n";
} else {
    echo "CNIC source image not found; skip image copy.\n";
}

$pdf = <<<'PDF'
%PDF-1.4
1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj
2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj
3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 612 792]/Contents 4 0 R/Resources<</Font<</F1 5 0 R>>>>>>endobj
4 0 obj<</Length 120>>stream
BT /F1 18 Tf 50 700 Td (HOME SERVICES - TECHNICIAN CERTIFICATE) Tj ET
BT /F1 12 Tf 50 660 Td (This is a sample certification document for admin preview.) Tj ET
BT /F1 12 Tf 50 630 Td (DUMMY DOCUMENT - FOR TESTING ONLY) Tj ET
endstream
endobj
5 0 obj<</Type/Font/Subtype/Type1/BaseFont/Helvetica>>endobj
xref
0 6
0000000000 65535 f 
0000000009 00000 n 
0000000058 00000 n 
0000000115 00000 n 
0000000264 00000 n 
0000000436 00000 n 
trailer<</Size 6/Root 1 0 R>>
startxref
515
%%EOF
PDF;

file_put_contents($dummyDir . '/certificate.pdf', $pdf);
echo "certificate.pdf generated.\n";
