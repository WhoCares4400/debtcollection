<?php
/*
 * Copyright (c) 2022. Jakub Turczyński
 *
 * Wszelkie prawa zastrzeżone. Poniższy kod źródłowy (zwany także programem komputerowym lub krótko - programem), zarówno w jego części twórczej jak i całości,  podlega ochronie na mocy prawa autorskiego jako utwór.
 * Użytkownikowi zezwala się na dostęp do kodu źródłowego oraz na jego użytkowanie w sposób w jaki program został do tego przeznaczony. Kopiowanie, powielanie czy edytowanie całości lub części kodu źródłowego programu bez zgody jego autora jest zabronione.
 */
?>

<!-- SIDEBAR -->
<div style="z-index: 20;" class="col-auto mx-0 px-0 pe-none">
    <div class="d-flex flex-column flex-shrink-0 p-0 pe-auto" style="width: 272px; min-width: 60px!important; max-width: 600px!important">
        <div class="col d-flex flex-column flex-shrink-0 p-3 text-white bg-darker" id="sidebar">
            <div class="p-2 sidebar-toggle-btn text-white text-end" title="Pokaż/Ukryj" data-hidden="false">
                <i class="bi bi-list"></i>
            </div>
            <a href="<?php echo $_SERVER['REQUEST_URI']; ?>" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none">
                <span class="mx-3 mt-2 fs-4 text-nowrap"><span class="sidebar-title-addition">Panel </span><span class="sidebar-title-company fw-bold">zarządzania</span></span>
            </a>
            <hr>
            <ul id="sidebar-manager-tabs" class="nav nav-pills flex-column mb-auto overflow-hidden">
                <li class="nav-item w-100" data-target="#debtcollection">
                    <a href="index.php" class="nav-link d-flex align-items-center text-white text-nowrap">
                        <i class="fs-4 bi bi-person-bounding-box"></i>
                        <span class="ms-2 nav-tab-title">Kontrola płatności</span>
                        <span class="ms-2 nav-tab-title-s d-none">KP</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
    <!-- RESIZER -->
    <div class="resizer p-0" id="dragMe"></div>

    <script>
        //Tabs
        const url = window.location.pathname;
        const filename = url.substring(url.lastIndexOf('/')+1);

        const currentTab = $('#sidebar-manager-tabs li a[href="'+filename+'"]');
        currentTab.addClass("active").attr("href", currentTab.parent().show().data("target")).attr("aria-current", "page");

        //definicja modulu sizeChanged dla jQuery
        (function ($) {
            $.fn.sizeChanged = function (handleFunction) {
                var element = this;
                var lastWidth = element.width();
                var lastHeight = element.height();

                setInterval(function () {
                    if (lastWidth === element.width()&&lastHeight === element.height())
                        return;
                    if (typeof (handleFunction) == 'function') {
                        handleFunction({ width: lastWidth, height: lastHeight },
                            { width: element.width(), height: element.height() });
                        lastWidth = element.width();
                        lastHeight = element.height();
                    }
                }, 100);

                return element;
            };
        }(jQuery));

        //Initialization
        managerTabsActions();
        sidebarResizer();

        $('.sidebar-toggle-btn').click(sidebarToggle);
        $('#sidebar-manager-tabs').sizeChanged(tabResizeHandler);

        $(document).ready(function() {
            if (window.screen.availWidth < 1200) {
                sidebarToggle();
            }
        })

        //Functions
        function tabResizeHandler() {
            var currWidth = $('#sidebar').parent().width();

            $('.nav-tab-title').each(function() {
                if (125 > currWidth) {
                    //mniejsze niz 115
                    $('#sidebar').addClass('px-1');
                    $('.sidebar-title-company').text('U');
                    $('.nav-tab-title-s').hide();
                } else {
                    //wieksze niz 115
                    $('#sidebar').removeClass('px-1');
                    $('.sidebar-title-company').text('zarządzania');
                    $('.nav-tab-title-s').show();
                }

                if ( 255 > currWidth ) {
                    //mniejsze niz 245
                    $(this).addClass('d-none');
                    $('.sidebar-title-addition').addClass('d-none');
                    $(this).siblings('.nav-tab-title-s').removeClass('d-none');
                } else {
                    //wieksze niz 245
                    $(this).removeClass('d-none');
                    $('.sidebar-title-addition').removeClass('d-none');
                    $(this).siblings('.nav-tab-title-s').addClass('d-none');
                }
            });

            sessionStorage.setItem( 'sidebarWidth', currWidth );
        }
        function getIntFromString(string) {
            return ((string.split('').map(function (char) {
                return char.charCodeAt(0);
            }).reduce((partialSum, a) => partialSum + a, 0) * 123) % 360) + 1;
        }
        function getColor( number = (360 * Math.random()) ) {
            return "hsl(" + number + ',90%,40%)';
        }
        function managerTabsActions() {
            $('#sidebar-manager-tabs li').click(function() {
                if (!$(this).find('a.active').length) {
                    $('#sidebar-manager-tabs li a.active').removeClass('active');
                    $(this).find('a').addClass('active');
                    $('.content:visible').hide();
                    $($(this).attr('data-target')).fadeIn('fast');
                }
            })
        }
        function sidebarToggle() {
            let sidebar = $('#sidebar'),
                sidebarToggleBtn = $('.sidebar-toggle-btn'),
                contentContainer = $('#contentContainer'),
                width = '-'+sidebar.parent().css('width');

            if (sidebar.css('left') == '0px') {
                sidebar.css('left', width);
                contentContainer.css('margin-left', width);
                $('#dragMe').hide();
                sidebarToggleBtn.attr("data-hidden", "true");
            } else {
                sidebar.css('left','0px');
                contentContainer.css('margin-left', '0px');
                $('#dragMe').show();
                sidebarToggleBtn.attr("data-hidden", "false");
            }
        }
        function sidebarResizer() {
            const resizer = document.getElementById('dragMe');
            const leftSide = resizer.previousElementSibling;
            const rightSide = resizer.nextElementSibling;

            // The current position of mouse
            let x = 0;
            let y = 0;
            let leftWidth = 0;

            // Handle the mousedown event
            const mouseDownHandler = function (e) {
                // Get the current mouse position
                x = e.clientX;
                y = e.clientY;
                leftWidth = leftSide.getBoundingClientRect().width;

                // Attach the listeners to `document`
                document.addEventListener('mousemove', mouseMoveHandler);
                document.addEventListener('mouseup', mouseUpHandler);
            };

            const mouseMoveHandler = function (e) {
                // How far the mouse has been moved
                const dx = e.clientX - x;

                const newLeftWidth = ((leftWidth + dx) * 100) / resizer.parentNode.getBoundingClientRect().width;
                leftSide.style.width = `${newLeftWidth}%`;

                resizer.style.cursor = 'col-resize';
                document.body.style.cursor = 'col-resize';

                leftSide.style.userSelect = 'none';
                leftSide.style.pointerEvents = 'none';

                rightSide.style.userSelect = 'none';
                rightSide.style.pointerEvents = 'none';
            };

            const mouseUpHandler = function () {
                resizer.style.removeProperty('cursor');
                document.body.style.removeProperty('cursor');

                leftSide.style.removeProperty('user-select');
                leftSide.style.removeProperty('pointer-events');

                rightSide.style.removeProperty('user-select');
                rightSide.style.removeProperty('pointer-events');

                // Remove the handlers of `mousemove` and `mouseup`
                document.removeEventListener('mousemove', mouseMoveHandler);
                document.removeEventListener('mouseup', mouseUpHandler);
            };

            // Attach the handler
            resizer.addEventListener('mousedown', mouseDownHandler);
        }
    </script>
    <style>
        #cursor-div {
            position:absolute;
            display:none
        }
        #sidebar {
            position:fixed;
            width:inherit;
            min-width:inherit;
            max-width:inherit;
            min-height:100vh;
            top:0;
            bottom:0;
            left:0;
            z-index:1;
            transition:.3s left
        }
        .sidebar-toggle-btn {
            position:absolute;
            top:0;
            right:-20px;
            width:38px;
            background-color:#1a1a1a;
            border-top-right-radius:5px;
            border-bottom-right-radius:5px;
            cursor:pointer;
            transition:.2s right
        }
        .sidebar-toggle-btn:hover {
            right:-35px
        }
        #dragMe.resizer {
            background-color:#1a1a1a;
            cursor:ew-resize;
            width:8px
        }
        .bg-darker {
            background-color:#1a1a1a
        }
        .sidebar-title-company {
            color:#0e79e7;
            text-shadow:0 0 4px #000
        }
        #sidebar-manager-tabs > .nav-item > .nav-link:not(.active):hover {
            background-color: #343434;
        }
    </style>
</div>