<?php

namespace App\Services;

use App\Models\FormCertificate;
use App\Models\Student;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class PrescriptionPdfService
{
    private const PAGE_WIDTH = 595.28;

    private const PAGE_HEIGHT = 841.89;

    public function ensureStored(FormCertificate $form, Student $student): bool
    {
        if ($form->pdf_path && Storage::disk('local')->exists($form->pdf_path)) {
            return true;
        }

        return $this->store($form, $student);
    }

    public function store(FormCertificate $form, Student $student): bool
    {
        $formTitle = $form->form_type === 'dentist_prescription'
            ? 'Dentist Prescription'
            : 'Physician Prescription';
        $path = 'prescriptions/'.$form->id.'.pdf';
        $pdf = $this->generate($form->form_data ?? [], $student->full_name, $formTitle);

        if (! Storage::disk('local')->put($path, $pdf)) {
            return false;
        }

        $form->update(['pdf_path' => $path]);

        return true;
    }

    public function generate(array $formData, string $studentName, string $formTitle): string
    {
        $isDentist = str_starts_with($formTitle, 'Dentist');
        $prefix = $isDentist ? 'rxd' : 'rx';
        $notes = $this->plainText((string) ($formData[$prefix.'_notes'] ?? ''));
        $noteLines = $this->wrapText($notes, 56);
        $date = $this->formatPrescriptionDate((string) ($formData[$prefix.'_date'] ?? ''));
        $studentName = $this->plainText($studentName);
        $age = $this->plainText((string) ($formData[$prefix.'_age'] ?? ''));
        $signer = $this->plainText((string) ($formData[$prefix.'_physician_name'] ?? ''));
        $designation = $isDentist ? 'School Dentist' : 'School Physician';
        $license = $this->plainText((string) ($formData[$prefix.'_physician_lic'] ?? ''));

        $images = [
            'Logo' => $this->readPngAsRgbImage(public_path('clinic/images/sunn-logo.png')),
            'Footer' => $this->readPngAsRgbImage(public_path('clinic/images/footer.png')),
        ];
        $pageStream = $this->renderPrescriptionSheet(
            $studentName,
            $age,
            $date,
            $noteLines,
            $signer,
            $designation,
            $license,
        );

        return $this->buildPdf($pageStream, $images);
    }

    private function renderPrescriptionSheet(
        string $studentName,
        string $age,
        string $date,
        array $noteLines,
        string $signer,
        string $designation,
        string $license,
    ): string {
        $margin = 34.02;
        $gap = 22.68;
        $cardWidth = (self::PAGE_WIDTH - ($margin * 2) - $gap) / 2;
        $cardHeight = (self::PAGE_HEIGHT - ($margin * 2) - $gap) / 2;
        $commands = [];

        for ($row = 0; $row < 2; $row++) {
            for ($column = 0; $column < 2; $column++) {
                $x = $margin + ($column * ($cardWidth + $gap));
                $y = $margin + ((1 - $row) * ($cardHeight + $gap));
                $this->renderPrescriptionCard(
                    $commands,
                    $x,
                    $y,
                    $cardWidth,
                    $cardHeight,
                    $studentName,
                    $age,
                    $date,
                    $noteLines,
                    $signer,
                    $designation,
                    $license,
                );
            }
        }

        return implode("\n", $commands);
    }

    private function renderPrescriptionCard(
        array &$commands,
        float $x,
        float $y,
        float $width,
        float $height,
        string $studentName,
        string $age,
        string $date,
        array $noteLines,
        string $signer,
        string $designation,
        string $license,
    ): void {
        $top = $y + $height;
        $commands[] = sprintf('0.6 w 0.1 0.1 0.1 RG %.2F %.2F %.2F %.2F re S', $x, $y, $width, $height);
        $commands[] = sprintf('q 34 0 0 40 %.2F %.2F cm /Logo Do Q', $x + 9, $top - 50);

        $textX = $x + 53;
        $this->addText($commands, 'F1', 4.7, $textX, $top - 16, 'Republic of the Philippines', '0 0.235 0.56');
        $this->addText($commands, 'F4', 5.2, $textX, $top - 24, 'STATE UNIVERSITY OF NORTHERN NEGROS', '0.96 0.75 0');
        $this->addText($commands, 'F4', 5.2, $textX, $top - 31, 'MEDICAL AND DENTAL SERVICES OFFICE', '0 0.235 0.56');
        $this->addText($commands, 'F1', 4.2, $textX, $top - 38, 'SUNN Main Campus', '0 0.235 0.56');
        $this->addText($commands, 'F1', 4.2, $textX, $top - 44, 'Sagay City, Negros Occidental', '0 0.235 0.56');
        $this->addText($commands, 'F1', 4.2, $textX, $top - 50, 'Philippines 6122', '0 0.235 0.56');

        $dividerY = $top - 58;
        $commands[] = sprintf('0.5 w 0 0 0 RG %.2F %.2F m %.2F %.2F l S', $x + 8, $dividerY, $x + $width - 8, $dividerY);

        $this->addText($commands, 'F2', 9.4, $x + 76, $top - 74, 'PRESCRIPTION FORM');
        $commands[] = sprintf('0.5 w 0 0 0 RG %.2F %.2F m %.2F %.2F l S', $x + 76, $top - 76, $x + 176, $top - 76);

        $patientY = $top - 91;
        $this->addText($commands, 'F4', 5.3, $x + 8, $patientY, 'Name:');
        $this->addText($commands, 'F1', 4.8, $x + 27, $patientY + 1, Str::limit($studentName, 29, ''));
        $commands[] = sprintf('0.45 w 0 0 0 RG %.2F %.2F m %.2F %.2F l S', $x + 26, $patientY - 2, $x + 95, $patientY - 2);
        $this->addText($commands, 'F4', 5.3, $x + 99, $patientY, 'Age:');
        $this->addText($commands, 'F1', 4.8, $x + 114, $patientY + 1, Str::limit($age, 8, ''));
        $commands[] = sprintf('0.45 w 0 0 0 RG %.2F %.2F m %.2F %.2F l S', $x + 113, $patientY - 2, $x + 133, $patientY - 2);
        $this->addText($commands, 'F4', 5.3, $x + 137, $patientY, 'Date:');
        $this->addText($commands, 'F1', 4.5, $x + 154, $patientY + 1, Str::limit($date, 19, ''));
        $commands[] = sprintf('0.45 w 0 0 0 RG %.2F %.2F m %.2F %.2F l S', $x + 153, $patientY - 2, $x + $width - 8, $patientY - 2);

        $bodyTop = $top - 124;
        $this->addText($commands, 'F2', 24, $x + 11, $bodyTop, 'R');
        $this->addText($commands, 'F4', 8, $x + 27, $bodyTop + 3, 'x');

        $lineY = $bodyTop - 13;
        foreach ($noteLines as $line) {
            if ($line !== '') {
                $this->addText($commands, 'F1', 7.2, $x + 43, $lineY, $line);
            }
            $lineY -= 9;
        }

        $this->addText($commands, 'F4', 5.7, $x + 8, $y + 56, Str::limit($signer, 42, ''));
        $this->addText($commands, 'F1', 5.3, $x + 8, $y + 47, $designation);
        $this->addText($commands, 'F1', 5.3, $x + 8, $y + 39, 'Lic. No.: '.$license);
        $commands[] = sprintf('q %.2F 0 0 39 %.2F %.2F cm /Footer Do Q', $width - 16, $x + 8, $y + 5);
    }

    private function formatPrescriptionDate(string $date): string
    {
        $date = $this->plainText($date);
        if ($date === '') {
            return now()->format('F j, Y');
        }

        $parsedDate = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);

        return $parsedDate && $parsedDate->format('Y-m-d') === $date
            ? $parsedDate->format('F j, Y')
            : $date;
    }

    /**
     * @return array{width: int, height: int, data: string}
     */
    private function readPngAsRgbImage(string $path): array
    {
        $png = file_get_contents($path);
        if ($png === false || ! str_starts_with($png, "\x89PNG\r\n\x1a\n")) {
            throw new RuntimeException('The prescription form image could not be read.');
        }

        $header = unpack('Nwidth/Nheight/CbitDepth/CcolorType/Ccompression/Cfilter/Cinterlace', substr($png, 16, 13));
        if (! is_array($header) || $header['bitDepth'] !== 8 || ! in_array($header['colorType'], [2, 6], true) || $header['interlace'] !== 0) {
            throw new RuntimeException('The prescription form image uses an unsupported PNG format.');
        }

        $compressedData = '';
        $offset = 8;
        $pngLength = strlen($png);
        while ($offset + 12 <= $pngLength) {
            $chunkLength = unpack('Nlength', substr($png, $offset, 4))['length'];
            $chunkType = substr($png, $offset + 4, 4);
            $chunkData = substr($png, $offset + 8, $chunkLength);
            if (strlen($chunkData) !== $chunkLength) {
                throw new RuntimeException('The prescription form image is incomplete.');
            }
            if ($chunkType === 'IDAT') {
                $compressedData .= $chunkData;
            }
            $offset += $chunkLength + 12;
            if ($chunkType === 'IEND') {
                break;
            }
        }

        $bytesPerPixel = $header['colorType'] === 6 ? 4 : 3;
        $rowLength = $header['width'] * $bytesPerPixel;
        $expectedLength = ($rowLength + 1) * $header['height'];
        $pixelData = gzuncompress($compressedData, $expectedLength);
        if ($pixelData === false || strlen($pixelData) !== $expectedLength) {
            throw new RuntimeException('The prescription form image pixel data could not be decoded.');
        }

        $rgbPixels = '';
        $previousRow = str_repeat("\0", $rowLength);
        for ($rowIndex = 0; $rowIndex < $header['height']; $rowIndex++) {
            $rowOffset = $rowIndex * ($rowLength + 1);
            $filter = ord($pixelData[$rowOffset]);
            $row = '';
            for ($byteIndex = 0; $byteIndex < $rowLength; $byteIndex++) {
                $left = $byteIndex >= $bytesPerPixel ? ord($row[$byteIndex - $bytesPerPixel]) : 0;
                $above = ord($previousRow[$byteIndex]);
                $upperLeft = $byteIndex >= $bytesPerPixel ? ord($previousRow[$byteIndex - $bytesPerPixel]) : 0;
                $rawByte = ord($pixelData[$rowOffset + $byteIndex + 1]);

                $predictor = match ($filter) {
                    0 => 0,
                    1 => $left,
                    2 => $above,
                    3 => intdiv($left + $above, 2),
                    4 => $this->paethPredictor($left, $above, $upperLeft),
                    default => throw new RuntimeException('The prescription form image has an invalid PNG filter.'),
                };
                $row .= chr(($rawByte + $predictor) & 255);
            }

            for ($pixelIndex = 0; $pixelIndex < $header['width']; $pixelIndex++) {
                $pixelOffset = $pixelIndex * $bytesPerPixel;
                $alpha = $bytesPerPixel === 4 ? ord($row[$pixelOffset + 3]) : 255;
                for ($channel = 0; $channel < 3; $channel++) {
                    $color = ord($row[$pixelOffset + $channel]);
                    $rgbPixels .= chr(intdiv(($color * $alpha) + (255 * (255 - $alpha)) + 127, 255));
                }
            }

            $previousRow = $row;
        }

        $compressedRgb = gzcompress($rgbPixels, 9);
        if ($compressedRgb === false) {
            throw new RuntimeException('The prescription form image could not be compressed for the PDF.');
        }

        return [
            'width' => $header['width'],
            'height' => $header['height'],
            'data' => $compressedRgb,
        ];
    }

    private function paethPredictor(int $left, int $above, int $upperLeft): int
    {
        $estimate = $left + $above - $upperLeft;
        $leftDistance = abs($estimate - $left);
        $aboveDistance = abs($estimate - $above);
        $upperLeftDistance = abs($estimate - $upperLeft);

        return $leftDistance <= $aboveDistance && $leftDistance <= $upperLeftDistance
            ? $left
            : ($aboveDistance <= $upperLeftDistance ? $above : $upperLeft);
    }

    private function plainText(string $value): string
    {
        return Str::ascii(strip_tags($value));
    }

    /**
     * @return array<int, string>
     */
    private function wrapText(string $value, int $width): array
    {
        if ($value === '') {
            return [];
        }

        $lines = [];
        foreach (preg_split('/\R/', $value) ?: [] as $paragraph) {
            if (trim($paragraph) === '') {
                $lines[] = '';

                continue;
            }

            array_push($lines, ...explode("\n", wordwrap(trim($paragraph), $width, "\n", true)));
        }

        return $lines;
    }

    private function addText(
        array &$commands,
        string $font,
        float $size,
        float $x,
        float $y,
        string $text,
        string $color = '0 0 0',
    ): void {
        if ($text === '') {
            return;
        }

        $commands[] = sprintf(
            'BT /%s %.2F Tf %s rg %.2F %.2F Td (%s) Tj ET',
            $font,
            $size,
            $color,
            $x,
            $y,
            $this->escape($text),
        );
    }

    private function escape(string $value): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $value);
    }

    /**
     * @param  array{width: int, height: int, data: string}  $image
     */
    private function imageObject(array $image): string
    {
        return '<< /Type /XObject /Subtype /Image /Width '.$image['width']
            .' /Height '.$image['height'].' /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /FlateDecode /Length '
            .strlen($image['data'])." >>\nstream\n".$image['data']."\nendstream";
    }

    /**
     * @param  array{Logo: array{width: int, height: int, data: string}, Footer: array{width: int, height: int, data: string}}  $images
     */
    private function buildPdf(string $pageStream, array $images): string
    {
        $objects = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            2 => '<< /Type /Pages /Kids [9 0 R] /Count 1 >>',
            3 => '<< /Type /Font /Subtype /Type1 /BaseFont /Times-Roman /Encoding /WinAnsiEncoding >>',
            4 => '<< /Type /Font /Subtype /Type1 /BaseFont /Times-Bold /Encoding /WinAnsiEncoding >>',
            5 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
            6 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>',
            7 => $this->imageObject($images['Logo']),
            8 => $this->imageObject($images['Footer']),
            9 => '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 '.self::PAGE_WIDTH.' '.self::PAGE_HEIGHT.'] /Resources << /Font << /F1 3 0 R /F2 4 0 R /F3 5 0 R /F4 6 0 R >> /XObject << /Logo 7 0 R /Footer 8 0 R >> >> /Contents 10 0 R >>',
            10 => '<< /Length '.strlen($pageStream)." >>\nstream\n".$pageStream."\nendstream",
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $number => $object) {
            $offsets[$number] = strlen($pdf);
            $pdf .= $number." 0 obj\n".$object."\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";
        for ($number = 1; $number <= count($objects); $number++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$number]);
        }

        return $pdf."trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n".$xrefOffset."\n%%EOF";
    }
}
