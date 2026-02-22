<?php
require_once INCLUDE_DIR . 'class.export.php';

class ExportAjaxAPI extends AjaxController {

    function check($id) {
        global $thisstaff;

        if (!$thisstaff)
            Http::response(403, 'Agent login is required');

        // Check session BEFORE loading: file may already be deleted after email
        // (happens in buffered environments like cPanel where ack() cannot flush
        // mid-execution, so export+email+delete all finish before browser gets
        // the 201 response) ** IMPULZZA NETWORKS **
        $inSession = isset($_SESSION['Exports'][$id]);
        $exporter  = Exporter::load($id);

        if (!$exporter || !$exporter->isAvailable()) {
            if ($inSession) {
                // Export completed and file was emailed+deleted — handle gracefully
                if ($_SERVER['REQUEST_METHOD'] === 'POST')
                    Http::response(201, $this->json_encode(['status' => 'emailed']));
                // For GET: fall through to template with flag set
                $exportEmailed = true;
            } else {
                Http::response(404, 'No such export');
            }
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($exporter->isReady())
                Http::response(201, $this->json_encode([
                            'status' => 'ready',
                            'href' => sprintf('export.php?id=%s',
                                $exporter->getId()),
                            'filename' => $exporter->getFilename()]));
            else // Export is not ready... checkback in a few
                Http::response(200, $this->json_encode([
                        'status' => 'notready']));
        }

        include STAFFINC_DIR . 'templates/export.tmpl.php';
    }
}
