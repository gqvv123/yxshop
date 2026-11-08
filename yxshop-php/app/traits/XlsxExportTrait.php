<?php

namespace app\traits;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use support\Response;

/**
 * 后台列表导出为 XLSX
 *
 * 前端统一用 responseType=blob 下载，因此这里直接返回二进制响应。
 */
trait XlsxExportTrait
{
    protected function xlsxDownload(array $headers, array $rows, string $filename): Response
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $startRow = 1;
        if ($headers) {
            $sheet->fromArray($headers, null, 'A1');
            $sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')->getFont()->setBold(true);
            $startRow = 2;
        }
        if ($rows) {
            $sheet->fromArray($rows, null, 'A' . $startRow);
        }

        foreach (range('A', $sheet->getHighestColumn()) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        ob_start();
        (new Xlsx($spreadsheet))->save('php://output');
        $content = ob_get_clean();
        $spreadsheet->disconnectWorksheets();

        return response($content, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Content-Length'      => (string) strlen($content),
        ]);
    }
}
