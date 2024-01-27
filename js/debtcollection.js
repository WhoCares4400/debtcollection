var debtsData = [];

var mainModal;
$(document).ready(function() {
    //GET DATA
    getDebtsData();

    //INITIALIZE MODAL
    mainModal = initializeModal('mainModal', true);
    secondModal = initializeModal('secondModal');
})

//DEBTS
//Main
function getDebtsData(refresh = true) {
    if (refresh) {
        mainLoaderOn();
    }

    $.post("handler/DebtCollectionHandler.php", {
        dc: true,
        get_debt_data: true
    }, function (response) {
        if (response != null && response != 'null') {
            console.log(response);
            try {
                if (response.status === "OK") {
                    debtsData.length = 0;
                    debtsData = response.data;
                }
            } catch (error) {
                console.log(error);
            }

            if (refresh) {
                if (!$.fn.DataTable.isDataTable('#debtsTable')) {
                    initializeDebtsTable();
                } else {
                    setTimeout(updateDebtsTable, 100);
                }
                mainLoaderOff();
            }
        }
    });
}
function getContractorData(companyId) {
    return new Promise((resolve, reject) => {
        $.post("handler/DebtCollectionHandler.php", {
            dc: true,
            get_contractor_data: true,
            company_id: companyId
        }, function (response) {
            if (response != null && response !== 'null') {
                try {
                    if (response.status === "OK") {
                        resolve( response.data );
                    } else {
                        reject(response);
                    }
                } catch (error) {
                    reject(error);
                }
            }
        });
    });
}

function initializeDebtsTable() {
    $('#debtsTable').dataTable({
        drawCallback: function () {
            initializeTooltips();
        },
        createdRow: function (row, data) {
            if (data.class != null) {
                $(row).addClass(data.class);
            }
            if (data.style != null) {
                $(row).attr("style", data.style);
            }
        },
        ordering: false,
        autoWidth: false,
        //stateSave: true,
        columns: [
            {data: 'company_id', width: "7%"},
            {data: 'invoice_id', width: "8%"},
            {data: 'dokument', width: "13%"},
            {data: 'data_wys', width: "10%"},
            {data: 'wartosc', width: "9%"},
            {data: 'zaplacil', width: "9%"},
            {data: 'saldo', width: "9%"},
            {data: 'termin', width: "8%"},
            {data: 'opoznienie', width: "10%"},
            {data: 'ost_akcja', width: "12%"},
            {data: 'wiecej', width: "5%"}
        ],
        rowId: function(d) {
            return 'id_' + d.raw_inv_id;
        },
        columnDefs: [{
            targets: '_all',
            className: 'align-middle text-center p-1'
        }],
        language: {
            "search": "Wyszukaj:",
            "lengthMenu": "Pokaż _MENU_ wyników na stronę",
            "zeroRecords": "Nie odnaleziono wyników",
            "info": "Pokazuję _START_-_END_ na _TOTAL_ wyników.",
            "infoEmpty": "Brak dostępnych wyników",
            "infoFiltered": "(przefiltrowano _MAX_ wyników)",
            "paginate": {
                "first": "Pierwsza",
                "last": "Ostatnia",
                "next": "Następna",
                "previous": "Poprzednia"
            }
        },
        pageLength: 50
    });

    $(document).trigger("dataTableReady");
    //ROZWINIECIE WIERSZA
    eventOnRowExpand();

    //AKTUALIZACJA TABELI
    updateDebtsTable();

    //ODSWIEZANIE DANYCH
    setInterval(function() {getDebtsData(false);}, 300000);
}
function updateDebtsTable(savePage = false) {
    loaderOn();
    //$('.ma-price').popover('hide');
    //console.log('%cAuctions table refresh...','color: #9400d1');

    setTimeout(function () {

        var rowsData = [];
        var table = $('#debtsTable');

        if (debtsData.length === 0) {
            rowsData.push( getEmptyRowData() );
        } else {
            var row = {};
            for (const [index, debt] of Object.entries(debtsData)) {
                row = getProcessedRowData(debt);
                if (row) {
                    rowsData.push(row);
                }
            }

            if (rowsData.length === 0) {
                rowsData.push( getEmptyRowData() );
            }
        }

        if (savePage) {
            var pageNum = table.DataTable().page();
        }

        table.dataTable().fnClearTable();
        table.dataTable().fnAddData(rowsData);

        if (savePage) {
            table.dataTable().fnPageChange(pageNum);
        }
        //initializePricePopovers();

        //console.log('%cAuctions table refresh completed','color: #9400d1; font-weight: bold;');
        loaderOff();
    },20);
}
function getProcessedRowData(debt, noFilter = false) {
    var row = {};

    let opoznienie = dateDiffInDays( new Date(debt.payment_due_date) );

    if (opoznienie > 0) {
        row.class = 'table-danger-subtle';
    }

    //Background color
    let addClass = 'border fw-500 py-1';
    let cR = 215,
        cG = 255,
        cB = 223,
        changeFactor = 8.5
    if (opoznienie > 1) {
        cR = 255;
        cG = (255 - (opoznienie * changeFactor));
        cB = (255 - (opoznienie * changeFactor));
    }
    if (opoznienie > 24) {
        addClass += ' text-light';
    }
    if (opoznienie > 30) {
        cR = (255 - ((opoznienie-30) * changeFactor));
    }
    let backgroundColor = 'rgba('+cR+', '+cG+', '+cB+', 0.8);';

    row.raw_inv_id = debt.invoice_id;

    row.company_id = "<div class=\"badge border text-dark bg-white m-1\">"+debt.company_id+"</div>";
    row.invoice_id = "<div class=\"badge border text-dark bg-white m-1\">"+debt.invoice_id+"</div>";
    row.dokument = '<div class="d-inline-block fw-500">'+debt.document_number+'</div>';
    row.data_wys = '<div class="fs-15p border-bottom border-secondary px-2 ">'+debt.issue_date+'</div>';
    row.wartosc = '<div class="fs-14p py-1 w-100">'+parseFloat(debt.amount_due).toFixed(2) +' zł</div>';
    row.zaplacil = '<div class="fs-14p py-1 w-100 text-success">'+parseFloat(debt.amount_paid).toFixed(2) +' zł</div>';
    row.saldo = '<div class="fs-14p fw-500 py-1 w-100 text-danger">'+ parseFloat(debt.balance).toFixed(2) +' zł</div>';
    row.termin = '<div class="fs-15p border-bottom border-secondary px-2 ">'+debt.payment_due_date+'</div>';
    row.opoznienie = '<div class="'+addClass+'" style="border-color: #a9a9a9; background-color: '+backgroundColor+'">'+ opoznienie +' dni</div>';
    row.ost_akcja = '';
    row.wiecej = '<div class="text-center fs-5 cursor-pointer dc-show-more no-select" title="Pokaż/Ukryj szczegóły"><i class="bi bi-chevron-down"></i></div>';

    //row.class
    //row.style

    return row;
}
function getEmptyRowData() {
    return {
        company_id: '',
        invoice_id: '',
        dokument: '',
        data_wys: '',
        wartosc: '',
        zaplacil: '',
        saldo: '',
        termin: '',
        opoznienie: '',
        ost_akcja: '',
        wiecej: ''
    };
}

function getDebtDataFromId(invoiceId) {
    return debtsData[(debtsData.findIndex(debt => debt.invoice_id == invoiceId))];
}
function additionalAuctionDataFormat(cData, dData = null) {

    console.log(cData);
    let opoznienie = dateDiffInDays( new Date(dData.payment_due_date) );

    // Contractor html
    let contractorHtml = `
        <h5>Dane kontrahenta</h5>
        <div class="border border-2 bg-white rounded-1 p-2">
            <div class="row align-items-start dc-kon-info-container">
                <div class="col">
                    <h1 class="lead text-black">`+cData.company_name+' <strong title="ID kontrahenta">('+cData.company_id+')</strong>'+`</h1>
                    <table class="table additional-table table-sm mb-0 fs-14p">
                        <tbody>
                            <tr>
                                <td><span class="fw-500">Adres:</span></td>
                                <td>`+cData.address+`</td>
                                <td><span class="fw-500">NIP:</span></td>
                                <td>`+cData.tax_id+`</td>
                            </tr>
                            <tr>
                                <td><span class="fw-500">Sposób płatności:</span></td>
                                <td>`+cData.payment_method+`</td>
                                <td><span class="fw-500">Limit kredytu:</span></td>
                                <td>`+cData.credit_limit+` <small>zł</small></td>
                            </tr>
                            <tr>
                                <td><span class="fw-500">Termin:</span></td>
                                <td>`+cData.payment_term_days+` <small>dni</small></td>
                                <td><span class="fw-500">Tolerancja:</span></td>
                                <td>`+cData.payment_tolerance_days+` <small>dni</small></td>
                            </tr>
                            <tr>
                                <td><span class="fw-500">Email:</span></td>
                                <td>`+cData.email+`</td>
                                <td><span class="fw-500">Telefon:</span></td>
                                <td>`+cData.phone_number+`</td>
                            </tr>
                            <tr>
                                <td><span class="fw-500">Obszar:</span></td>
                                <td>`+cData.area+`</td>
                                <td><span class="fw-500">Transport:</span></td>
                                <td>`+cData.transport+`</td>
                            </tr>
                            <tr>
                                <td><span class="fw-500">Fax:</span></td>
                                <td>`+cData.fax+`</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>`;

    // Invoice html
    let invoiceHtml = `
        <h5>Szczegóły dokumentu</h5>
        <div class="border border-2 bg-white rounded-1 p-2">
            <div class="row align-items-start dc-inv-info-container">
                <div class="col">
                    <h1 class="lead text-black">`+dData.document_number+' <strong title="ID dokumentu">('+dData.invoice_id+')</strong>'+`</h1>
                    <table class="table additional-table table-sm mb-0 fs-14p">
                        <tbody>
                            <tr>
                                <td><span class="fw-500">ID dokumentu:</span></td>
                                <td>`+dData.invoice_id+`</td>
                                <td><span class="fw-500">Numer referencyjny:</span></td>
                                <td>`+dData.document_number+`</td>
                            </tr>
                            <tr>
                                <td><span class="fw-500">Data wystawienia:</span></td>
                                <td>`+dData.issue_date+`</td>
                                <td><span class="fw-500">Wartość:</span></td>
                                <td>`+dData.amount_due+`</td>
                            </tr>
                            <tr>
                                <td><span class="fw-500">Sposób płatności:</span></td>
                                <td>`+dData.payment_method+`</td>
                                <td><span class="fw-500">Termin płatności:</span></td>
                                <td class="fw-500 ${opoznienie > 0 ? 'text-danger' : 'text-success'}">`+dData.payment_due_date+`</td>
                            </tr>
                            <tr>
                                <td><span class="fw-500">Opłacona wartość:</span></td>
                                <td>`+dData.amount_paid+`</td>
                                <td><span class="fw-500">Saldo:</span></td>
                                <td class="fw-500 ${0 > dData.balance ? 'text-danger' : ''}">`+dData.balance+`</td>
                            </tr>
                            <tr>
                                <td><span class="fw-500">Uwagi:</span></td>
                                <td>`+dData.remarks+`</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>`;

    return '<div class="slider bg-light">' +
            '<div class="container-fluid py-2" id="kon-'+cData.company_id+'-expand">' +
                '<div class="row align-items-start">' +
                    '<div class="col-md-6">' +
                        contractorHtml +
                    '</div>' +
                    '<div class="col-md-6">' +
                        invoiceHtml +
                    '</div>' +
                '</div>' +
            '</div>' +
        '</div>';
}
//Events
function eventOnRowExpand() {
    $('#debtsTable').on('click', 'div.dc-show-more', function () {
        var table = $('#debtsTable');
        var dataTable = table.DataTable();
        var tr = $(this).closest('tr');
        var row = dataTable.row( tr );

        //Hide previous row
        var hideTr = table.find('.dt-hasChild');
        var hideRow = dataTable.row( hideTr );

        $('.chevron-selected').removeClass('chevron-selected');

        if ( hideRow.child.isShown() ) {
            hideTr.find(".bi-chevron-up").removeClass("bi-chevron-up").addClass("bi-chevron-down");
            $('div.slider', hideRow.child()).slideUp( 'fast', function() {
                hideRow.child.hide();
                hideTr.removeClass('shown');
            } );
        }

        //Expand/shrink
        if ( row.child.isShown() ) {
            $(this).html('<i class="bi bi-chevron-down chevron-selected"></i>');
            $('div.slider', row.child()).slideUp( 'fast', function() {
                row.child.hide();
                tr.removeClass('shown');
            } );
        }
        else
        {
            let debtData = getDebtDataFromId(row.data().raw_inv_id);
            $(this).html('<span class="spinner-border spinner-border-sm border-2" role="status" aria-hidden="true"></span>');

            getContractorData(debtData.company_id).then((konData) => {
                $(this).html('<i class="bi bi-chevron-up chevron-selected"></i>');

                debtData.kon_data = konData;

                //Dane kontrahenta
                row.child( additionalAuctionDataFormat(konData, debtData), 'no-padding' ).show();

                //Wyswietlanie pola
                tr.addClass('shown');
                $('div.slider', row.child()).slideDown('fast', function() {
                    this.scrollIntoView({
                        behavior: "smooth",
                        block: "nearest"
                    });
                });
            });
        }
    } );
}

function initializeModal(modalId, draggable = false) {
    modal = new bootstrap.Modal(document.getElementById(modalId), {
        keyboard: true,
        backdrop: true
    });
    modal.body = function(content) {
        let body = $(this._element).find('.modal-content .modal-body');
        return (typeof content !== 'undefined') ? body.html(content) : body.html();
    }
    modal.header = function(content) {
        let header = $(this._element).find('.modal-content .modal-header');
        return (typeof content !== 'undefined') ? header.html(content) : header.html();
    }
    modal.footer = function(content) {
        let footer = $(this._element).find('.modal-content .modal-footer');
        return (typeof content !== 'undefined') ? footer.html(content) : footer.html();
    }

    if (draggable) {
        $('#' + modalId + ' .modal-content').draggable({
            handle: ".modal-header",
            containment: "window"
        }).find('.modal-header').css("cursor", "move");
    }

    return modal;
}

//UTILITIES
function initializeTooltips() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl, {html: true, sanitize: false})
    })
}
function showToast(message, title = 'Alert', time = 'Teraz', autoHide = true, color = '#e30000') {
    let toastBody = `<div class="toast" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header">
                    <svg class="bd-placeholder-img rounded me-2" width="20" height="20" aria-hidden="true" preserveAspectRatio="xMidYMid slice" focusable="false">
                        <rect width="100%" height="100%" fill="`+color+`"></rect>
                    </svg>
                    <strong class="me-auto">`+title+`</strong>
                    <small>`+time+`</small>
                    <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Zamknij"></button>
                </div>
                <div class="toast-body">
                    `+message+`
                </div>
            </div>`;
    $('.toast-container').append(toastBody);

    var toastElement = $('.toast').last(),
        toast = new bootstrap.Toast( toastElement, {autohide: autoHide} );

    $(toastElement).on('hidden.bs.toast', function() { $(this).remove(); });
    toast.show();
}
function getPercentage(partialValue, totalValue) {
    return (100 * partialValue) / totalValue;
}
function addDays(days, date = new Date()) {
    var result = new Date(date);
    result.setDate(result.getDate() + days);
    return result;
}
function mergeSortedArrays(arr1 = [], arr2 = []) {
    const res = [];
    let i = 0;
    let j = 0;
    while(i < arr1.length && j < arr2.length){
        if(arr1[i] < arr2[j]){
            res.push(arr1[i]);
            i++;
        }else{
            res.push(arr2[j]);
            j++;
        }
    }
    while(i < arr1.length){
        res.push(arr1[i]);
        i++;
    }
    while(j < arr2.length){
        res.push(arr2[j]);
        j++;
    }
    return res;
}
function numberBetweenIncl(num, l, h) {
    return (num >= l && num <= h);
}
function getFormattedDate(date = new Date()) {
    var dt = new Date(date);
    var month = dt.getMonth() + 1;
    var day = dt.getDate();
    var year = dt.getFullYear();

    if(month < 10)
        month = '0' + month.toString();
    if(day < 10)
        day = '0' + day.toString();

    return year + '-' + month + '-' + day;
}
function parseDate(date) {
    return (date === "") ? "" : date.substring(0,4) + '-' + date.substring(4,6) + '-' + date.substring(6,8);
}
function getKeyByValue(object, value) {
    return Object.keys(object).find(key => object[key] === value);
}
function getObjectFromArrayByAttributeValue(arrayOfObjects, attribute, value) {
    return arrayOfObjects[(arrayOfObjects.findIndex(object => object[attribute] == value))];
}

function eventExpandPanelButtonClicked() {
    $(document).on("click", 'button[aria-controls="debtPanelTop"]', function() {
        if ( $(this).prop("ariaExpanded") === "true") {
            $(this).find('i.bi').removeClass("bi-plus-circle").addClass("bi-dash-circle");
            $(this).find('span').text("Ukryj filtry");
        } else {
            $(this).find('i.bi').removeClass("bi-dash-circle").addClass("bi-plus-circle");
            $(this).find('span').text("Pokaż filtry");
        }
    })
    $(document).on("click", 'button[aria-controls="remindersPanel"]', function() {
        if ( $(this).prop("ariaExpanded") === "true") {
            $(this).find('i.bi').removeClass("bi-plus-circle").addClass("bi-dash-circle");
            $(this).find('span').text("Ukryj przypomnienia");
        } else {
            $(this).find('i.bi').removeClass("bi-dash-circle").addClass("bi-plus-circle");
            $(this).find('span').text("Pokaż przypomnienia");
        }
    })
}
function eventDcSkippedDatesChange() {
    $('#dcSkippedFrom').on('change', function() {
        $('#dcSkippedTo').prop('min', $(this).val()).trigger('change');
        if ( $('input#dc-skipped-actions').is(':checked') ) {
            updateDebtsTable()
        }
    });
    $('#dcSkippedTo').prop('min', getFormattedDate).on('change', function() {
        var setValue = $(this).val(),
            minValue = $(this).prop('min'),
            setDate = new Date( setValue ),
            minDate = new Date( minValue );
        if ( minDate.getTime() > setDate.getTime() ) {
            $(this).val( minValue );
        }

        if ( $('input#dc-skipped-actions').is(':checked') ) {
            updateDebtsTable()
        }
    })
}

//FUNKCJE WŁĄCZAJĄCE I WYŁĄCZAJĄCE OKNO ŁADOWANIA
var loaderInterval = false;
function mainLoaderOn() {
    //reset interval
    if (loaderInterval) {
        clearInterval(loaderInterval);
        loaderInterval = false;
    }

    //fake loading sequence start
    $('#mainLoader .progress-bar').width('0%');
    $('#mainLoader .text-primary').html('<div class="text-primary fs-4 mt-1 fw-bold">Ładowanie danych... (<span class="percent">0</span>%)</div>');
    $('#mainLoader').show();
    $('.content-loader').addClass('d-none');

    var currWidth = 0, //starting width
        widthStep = 1,
        stepInterval = 50;
    loaderInterval = setInterval(function () {
        if (currWidth === 97) {
            clearInterval(loaderInterval);
            loaderInterval = false;
            setTimeout(function() {
                $('#mainLoader .progress-bar').width('98%');
                $('#mainLoader .percent').text('98');
            }, 2000);
        }
        $('#mainLoader .progress-bar').width(currWidth + '%');
        $('#mainLoader .percent').text(currWidth);
        currWidth += widthStep;
    }, stepInterval);

}
function mainLoaderOff() {
    //reset interval
    clearInterval(loaderInterval);
    loaderInterval = false;

    //fake loading sequence end
    $('#mainLoader .progress-bar').width('99%');
    $('#mainLoader .text-primary').html('<div class="text-primary fs-4 mt-1 fw-bold">Już prawie... (<span class="percent">99</span>%)</div>');

    let waitT = 200;
    //wait 200ms
    setTimeout(function() {
        $('#mainLoader .progress-bar').width('100%');
        $('#mainLoader .text-primary').html('<div class="text-primary fs-4 mt-1 fw-bold">Załadowano! (<span class="percent">100</span>%)</div>');
    }, waitT);
    //wais 200ms more
    setTimeout(function() {
        $('#mainLoader').hide();
        $('.content-loader').removeClass('d-none');
    },waitT + 200);
}

function loaderOn() {
    $('.content-loader').show();
}
function loaderOff() {
    $('.content-loader').hide();
}

function dateDiffInDays(a, b = new Date()) {
    const _MS_PER_DAY = 1000 * 60 * 60 * 24;
    // Discard the time and time-zone information.
    const utc1 = Date.UTC(a.getFullYear(), a.getMonth(), a.getDate());
    const utc2 = Date.UTC(b.getFullYear(), b.getMonth(), b.getDate());

    return Math.floor((utc2 - utc1) / _MS_PER_DAY);
}
const datesAreOnSameDay = (first, second) =>
    first.getFullYear() === second.getFullYear() &&
    first.getMonth() === second.getMonth() &&
    first.getDate() === second.getDate();