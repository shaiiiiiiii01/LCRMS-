<div class="admin-modal case-import-modal" data-case-import-modal hidden>
    <div class="admin-modal-backdrop" data-close-case-import></div>
    <section class="admin-modal-panel small" role="dialog" aria-modal="true" aria-labelledby="caseImportModalTitle">
        <div class="admin-modal-head">
            <div>
                <h3 id="caseImportModalTitle" data-case-import-title>Import Excel</h3>
                <p data-case-import-subtitle>Select an Excel file containing case records.</p>
            </div>
            <button class="admin-icon-button" type="button" data-close-case-import aria-label="Close import form">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"></path></svg>
            </button>
        </div>

        <form class="settings-form case-import-form" action="cases_api.php?action=import" method="post" enctype="multipart/form-data" data-case-import-form novalidate>
            <div class="admin-form-group" data-case-import-file-group>
                <label for="caseImportFile">Excel File</label>
                <input id="caseImportFile" name="excel_file" type="file" accept=".xlsx,.xls,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel" data-case-import-file required>
            </div>
            <p class="form-alert" data-case-import-error hidden></p>
            <div class="case-import-summary" data-case-import-summary hidden></div>
            <div class="modal-actions">
                <button class="secondary-button" type="button" data-close-case-import data-case-import-cancel>Cancel</button>
                <button class="admin-primary-button compact" type="submit" data-case-import-submit>Upload</button>
                <button class="admin-primary-button compact" type="button" data-case-import-ok hidden>OK</button>
            </div>
        </form>
    </section>
</div>
