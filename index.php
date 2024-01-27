<?php
session_start();
setcookie("up_token", $_SESSION['token']);

?>
<html lang="pl-PL">
<head>

    <title>Panel zarządzania - Kontrola płatności</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=10; IE=9; IE=8; IE=7; IE=EDGE" />

    <!-- JQUERY -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.13.2/themes/smoothness/jquery-ui.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.13.2/jquery-ui.min.js"></script>
    <!-- MAIN -->
    <script>
        const userName = '<?=$_SESSION['user']?>';
        const serverAddr = "<?=$_SERVER['SERVER_ADDR']?>";
    </script>
    <link rel="stylesheet" href="css/debtcollection.css<?=('?r='.time())?>">
    <script src="js/debtcollection.js<?=('?r='.time())?>"></script>
    <!-- JQUERY TIMESPACE -->
    <link rel="stylesheet" href="js/timespace/css/jquery.timespace.light.css">
    <script type="text/javascript" src="js/timespace/jquery.timespace.js"></script>
    <!-- JQUERY FLOATTHEAD -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/floatthead/2.2.5/jquery.floatThead.min.js" integrity="sha512-131fDtJKn0jUOqN1sfcHkBZHRmTCP0gmcpztVNuE3M8toiuIv8V9I+tpL/1t3GFDBcigLyB2hWJ3ZNjYwEO4bg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <!-- BOOTSTRAP -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
    <!-- BOOTSTRAP ICONS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.8.0/dist/chart.min.js" integrity="sha256-cHVO4dqZfamRhWD7s4iXyaXWVK10odD+qp4xidFzqTI=" crossorigin="anonymous"></script>
    <!-- DATATABLES BOOTSTRAP -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
    <script type="text/javascript" src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>

    <?php
        if (isset($_GET['skipDate']) && strlen($_GET['skipDate']) > 7) {
            $skipDate = substr($_GET['skipDate'], 0, 4).'-'.substr($_GET['skipDate'], 4, 2).'-'.substr($_GET['skipDate'], 6, 2);
            echo '<script type="text/javascript">$(document).on("dataTableReady",()=>{$("#dcSkippedFrom").val("'. $skipDate .'");$("#dcSkippedTo").val("'. $skipDate .'");$("#dc-skipped-actions").prop("checked",true).trigger("change");})</script>';
        }
    ?>
</head>

<body>
<main>
    <div class="container-fluid">
        <div class="row flex-nowrap" style="min-height: 100vh;">

            <?php include("view/sidebar.php"); ?>

            <!-- CONTENT -->
            <div class="col d-flex flex-column flex-shrink-0 p-0" id="contentContainer">

                <!-- CONTENT - DEBT COLLECTION -->
                <div class="container-fluid p-2 content" id="debtCollection">

                    <div class="container-fluid p-0">
                        <!-- Header -->
                        <div class="w-100 d-inline-flex justify-content-between">
                            <div class="d-inline-flex mb-2 ms-3">
                                <div class="ratio ratio-1x1 rounded-2 section-icon me-2">
                                    <h5 class="d-flex align-items-center justify-content-center text-white">
                                        <i class="bi bi-person-bounding-box"></i>
                                    </h5>
                                </div>
                                <div class="section-title">
                                    <h3 class="mb-0 border-bottom">Kontrola płatności</h3>
                                </div>
                            </div>
                        </div>

                        <div class="container-fluid card box-shadow-1 my-3 px-1 px-md-2" id="debtPanelTable">
                            <div class="my-3">
                                <table id="debtsTable" class="table">
                                    <thead>
                                        <tr>
                                            <th scope="col" class="text-center">ID Firmy</th>
                                            <th scope="col" class="text-center">ID Dokumentu</th>
                                            <th scope="col" class="text-center">Dokument</th>
                                            <th scope="col" class="text-center">Data wystawienia</th>
                                            <th scope="col" class="text-center">Wartość</th>
                                            <th scope="col" class="text-center">Zapłacił</th>
                                            <th scope="col" class="text-center">Saldo</th>
                                            <th scope="col" class="text-center">Termin</th>
                                            <th scope="col" class="text-center">Dni po terminie</th>
                                            <th scope="col" class="text-center">Status</th>
                                            <th scope="col" class="text-center"><i class="bi bi-three-dots"></i></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>
                                                <div class="d-flex justify-content-center">
                                                    <div class="spinner-border" role="status">
                                                        <span class="visually-hidden">Loading...</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex justify-content-center">
                                                    <div class="spinner-border" role="status">
                                                        <span class="visually-hidden">Loading...</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex justify-content-center">
                                                    <div class="spinner-border" role="status">
                                                        <span class="visually-hidden">Loading...</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex justify-content-center">
                                                    <div class="spinner-border" role="status">
                                                        <span class="visually-hidden">Loading...</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex justify-content-center">
                                                    <div class="spinner-border" role="status">
                                                        <span class="visually-hidden">Loading...</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex justify-content-center">
                                                    <div class="spinner-border" role="status">
                                                        <span class="visually-hidden">Loading...</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex justify-content-center">
                                                    <div class="spinner-border" role="status">
                                                        <span class="visually-hidden">Loading...</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex justify-content-center">
                                                    <div class="spinner-border" role="status">
                                                        <span class="visually-hidden">Loading...</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex justify-content-center">
                                                    <div class="spinner-border" role="status">
                                                        <span class="visually-hidden">Loading...</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex justify-content-center">
                                                    <div class="spinner-border" role="status">
                                                        <span class="visually-hidden">Loading...</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex justify-content-center">
                                                    <div class="spinner-border" role="status">
                                                        <span class="visually-hidden">Loading...</span>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</main>

<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1070;">
    <!-- Toasts will be inserted automatically -->
</div>

<!-- Modal -->
<div class="modal fade" id="mainModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="mainModal" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="staticBackdropLabel">Modal title</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                ...
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Zamknij</button>
            </div>
        </div>
    </div>
</div>

<!-- Secondary modal -->
<div class="modal fade" id="secondModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="secondModal" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="staticBackdropLabel">Podgląd</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="background: #fcfefe;">
                ...
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Zamknij</button>
            </div>
        </div>
    </div>
</div>

<div class="content-loader" style="display: none;">
    <div class="d-flex flex-column h-100 align-items-center justify-content-center">
        <div class="spinner-border text-primary" style="width: 5rem; height: 5rem;" role="status"></div>
    </div>
</div>
<div id="mainLoader" style="display: none;">
    <div class="d-flex flex-column h-100 align-items-center justify-content-center user-select-none">
        <div class="text-primary fs-4 mt-1 fw-bold">Ładowanie danych... (<span class="percent">0</span>%)</div>
    </div>
    <div class="progress w-50 position-relative ms-auto me-auto" style="margin-top: -46vh;">
        <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%;"></div>
    </div>
</div>

</body>
</html>