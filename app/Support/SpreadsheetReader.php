<?php

namespace App\Support;

use App\Exceptions\BusinessRuleException;
use SimpleXMLElement;
use ZipArchive;

/**
 * Đọc bảng dữ liệu đơn giản từ file Excel (.xlsx) hoặc CSV, trả về mảng các dòng (mỗi dòng là mảng chuỗi).
 * Chỉ đọc sheet đầu tiên của file Excel. Không cần thư viện ngoài (dùng extension zip có sẵn của PHP).
 */
class SpreadsheetReader
{
    /**
     * @param  string|null  $sheetName  Tên trang tính cần đọc (chỉ với .xlsx); để trống = trang đầu tiên.
     * @return array<int, array<int, string>>
     */
    public static function read(string $path, string $extension, ?string $sheetName = null): array
    {
        return match (strtolower($extension)) {
            'xlsx' => static::readXlsx($path, $sheetName),
            'csv', 'txt' => static::readCsv((string) file_get_contents($path)),
            default => throw new BusinessRuleException('Chỉ hỗ trợ file Excel (.xlsx) hoặc CSV (.csv).'),
        };
    }

    /** @return array<int, array<int, string>> */
    public static function readCsv(string $content): array
    {
        // Bỏ ký tự BOM mà Excel thêm vào đầu file "CSV UTF-8".
        if (str_starts_with($content, "\xEF\xBB\xBF")) {
            $content = substr($content, 3);
        }

        if (! mb_check_encoding($content, 'UTF-8')) {
            throw new BusinessRuleException(
                'File CSV không ở dạng UTF-8 nên tiếng Việt sẽ bị lỗi. Trong Excel hãy chọn Save As → "CSV UTF-8 (Comma delimited)", hoặc tải lên trực tiếp file Excel (.xlsx).'
            );
        }

        $content = str_replace(["\r\n", "\r"], "\n", $content);

        // Excel ở máy cài tiếng Việt thường dùng dấu chấm phẩy (;) làm dấu phân cách, máy khác dùng dấu phẩy (,).
        $firstLine = strtok($content, "\n") ?: '';
        $counts = [',' => substr_count($firstLine, ','), ';' => substr_count($firstLine, ';'), "\t" => substr_count($firstLine, "\t")];
        arsort($counts);
        $delimiter = array_key_first($counts);

        $handle = fopen('php://temp', 'r+');
        fwrite($handle, $content);
        rewind($handle);

        $rows = [];
        while (($row = fgetcsv($handle, null, $delimiter, '"', '')) !== false) {
            $rows[] = array_map(fn ($v) => trim((string) $v), $row);
        }
        fclose($handle);

        return $rows;
    }

    /** @return array<int, array<int, string>> */
    public static function readXlsx(string $path, ?string $sheetName = null): array
    {
        if (! class_exists(ZipArchive::class)) {
            throw new BusinessRuleException(
                'Máy chủ chưa bật extension "zip" của PHP nên chưa đọc được file Excel. Hãy lưu file thành "CSV UTF-8 (Comma delimited)" rồi tải lên, hoặc nhờ quản trị bật extension=zip trong php.ini.'
            );
        }

        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            throw new BusinessRuleException('Không mở được file Excel. Hãy kiểm tra file có đúng định dạng .xlsx không.');
        }

        try {
            $sheetPath = static::sheetPath($zip, $sheetName);
            $sheetXml = $sheetPath ? $zip->getFromName($sheetPath) : false;
            if ($sheetXml === false) {
                throw new BusinessRuleException($sheetName !== null
                    ? "Không tìm thấy trang tính tên \"{$sheetName}\" trong file Excel."
                    : 'Không tìm thấy trang tính (sheet) nào trong file Excel.');
            }

            $sharedStrings = static::sharedStrings($zip);
            $sheet = static::xml($sheetXml);
        } finally {
            $zip->close();
        }

        $rows = [];
        foreach ($sheet->sheetData->row ?? [] as $rowNode) {
            $row = [];
            foreach ($rowNode->c as $cell) {
                $index = static::columnIndex((string) $cell['r']);
                $type = (string) $cell['t'];

                $value = match ($type) {
                    's' => $sharedStrings[(int) $cell->v] ?? '',
                    'inlineStr' => static::richText($cell->is),
                    default => (string) $cell->v,
                };

                $row[$index ?? count($row)] = trim($value);
            }

            if ($row !== []) {
                $max = max(array_keys($row));
                $rows[] = array_map(fn ($i) => $row[$i] ?? '', range(0, $max));
            } else {
                $rows[] = [];
            }
        }

        return $rows;
    }

    /** Đường dẫn XML của trang tính theo tên (hoặc trang đầu tiên khi $sheetName = null). */
    protected static function sheetPath(ZipArchive $zip, ?string $sheetName): ?string
    {
        $workbook = $zip->getFromName('xl/workbook.xml');
        $rels = $zip->getFromName('xl/_rels/workbook.xml.rels');

        if ($workbook !== false && $rels !== false) {
            $wb = static::xml($workbook);
            $first = null;
            foreach ($wb->sheets->sheet ?? [] as $sheet) {
                if ($sheetName === null || trim((string) $sheet['name']) === trim($sheetName)) {
                    $first = $sheet;
                    break;
                }
            }
            if ($first) {
                $rid = (string) $first->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'];
                foreach (static::xml($rels)->Relationship as $rel) {
                    if ((string) $rel['Id'] === $rid) {
                        $target = ltrim((string) $rel['Target'], '/');

                        return str_starts_with($target, 'xl/') ? $target : 'xl/'.$target;
                    }
                }
            }
        }

        if ($sheetName !== null) {
            return null;
        }

        return $zip->locateName('xl/worksheets/sheet1.xml') !== false ? 'xl/worksheets/sheet1.xml' : null;
    }

    /** @return array<int, string> */
    protected static function sharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if ($xml === false) {
            return [];
        }

        $strings = [];
        foreach (static::xml($xml)->si as $si) {
            $strings[] = static::richText($si);
        }

        return $strings;
    }

    /** Ghép chữ trong 1 ô, kể cả khi ô có nhiều đoạn định dạng khác nhau (rich text). */
    protected static function richText(?SimpleXMLElement $node): string
    {
        if (! $node) {
            return '';
        }
        if (isset($node->t)) {
            return (string) $node->t;
        }

        $text = '';
        foreach ($node->r as $run) {
            $text .= (string) $run->t;
        }

        return $text;
    }

    /**
     * Đổi giá trị ô ngày tháng sang Carbon. Nhận số ngày kiểu Excel (vd 46328 hoặc 46328.55 có giờ)
     * hoặc chuỗi dd/mm/yyyy (có thể kèm giờ). Trả về null nếu ô trống hoặc không phải ngày hợp lệ.
     */
    public static function toDate(?string $value): ?\Carbon\Carbon
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        if (is_numeric($value)) {
            $serial = (float) $value;
            if ($serial < 1 || $serial > 2958465) {
                return null;
            }
            // Excel đếm ngày từ 30/12/1899. Tính theo UTC rồi lấy đúng "giờ đồng hồ" đó ở múi giờ của hệ thống,
            // tránh lệch vài phút do múi giờ lịch sử năm 1899 của Việt Nam (+07:06:40).
            $wall = \Carbon\Carbon::create(1899, 12, 30, 0, 0, 0, 'UTC')->addSeconds((int) round($serial * 86400));

            return \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $wall->format('Y-m-d H:i:s'));
        }

        if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})(?:\s+(\d{1,2}):(\d{2})(?::(\d{2}))?)?$#', $value, $m)) {
            [$d, $mo, $y] = [(int) $m[1], (int) $m[2], (int) $m[3]];
            if ($y < 2000 || ! checkdate($mo, $d, $y)) {
                return null;
            }

            return \Carbon\Carbon::create($y, $mo, $d, (int) ($m[4] ?? 0), (int) ($m[5] ?? 0), (int) ($m[6] ?? 0));
        }

        return null;
    }

    /** "A1" -> 0, "B7" -> 1, "AA3" -> 26 */
    protected static function columnIndex(string $ref): ?int
    {
        if (! preg_match('/^([A-Z]+)\d+$/', strtoupper($ref), $m)) {
            return null;
        }

        $index = 0;
        foreach (str_split($m[1]) as $ch) {
            $index = $index * 26 + (ord($ch) - 64);
        }

        return $index - 1;
    }

    protected static function xml(string $content): SimpleXMLElement
    {
        $xml = simplexml_load_string($content, SimpleXMLElement::class, LIBXML_NONET);
        if ($xml === false) {
            throw new BusinessRuleException('Nội dung file Excel bị lỗi, không đọc được.');
        }

        return $xml;
    }
}
