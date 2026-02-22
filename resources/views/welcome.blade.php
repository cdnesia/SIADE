<!doctype html>
<html lang="en">

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!--favicon-->
    <link rel="icon" href="assets/images/favicon-32x32.png" type="image/png" />
    <!--plugins-->
    <link href="assets/plugins/simplebar/css/simplebar.css" rel="stylesheet" />
    <link href="assets/plugins/perfect-scrollbar/css/perfect-scrollbar.css" rel="stylesheet" />
    <link href="assets/plugins/metismenu/css/metisMenu.min.css" rel="stylesheet" />
    <!-- loader-->
    <link href="assets/css/pace.min.css" rel="stylesheet" />
    <script src="assets/js/pace.min.js"></script>
    <!-- Bootstrap CSS -->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/bootstrap-extended.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="assets/css/app.css" rel="stylesheet">
    <link href="assets/css/icons.css" rel="stylesheet">
    <title>Rocker - Bootstrap 5 Admin Dashboard Template</title>
    <style>
        * {
            font-family: 'Poppins' !important;
            font-weight: 500;
            /* atau 600 / 700 */
        }
    </style>
</head>

<body class="">
    <!--wrapper-->
    <div class="wrapper">
        <header class="login-header shadow">
            <nav
                class="navbar navbar-expand-lg navbar-light rounded-0 bg-white fixed-top rounded-0 shadow-none border-bottom">
                <div class="container">
                    <a class="navbar-brand" href="{{ url('/') }}">
                        <img src="{{ asset('') }}assets//images/simawa-logo.png" width="140" alt="logo-simawa" />
                    </a>
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                        data-bs-target="#navbarSupportedContent1" aria-controls="navbarSupportedContent1"
                        aria-expanded="false" aria-label="Toggle navigation"> <span class="navbar-toggler-icon"></span>
                    </button>
                    <div class="collapse navbar-collapse" id="navbarSupportedContent1">
                        <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                            <li class="nav-item"> <a class="nav-link active" aria-current="page" href="#"><i
                                        class='bx bx-home-alt me-1'></i>Beranda</a>
                            </li>
                            <li class="nav-item"> <a class="nav-link" href="#"><i
                                        class='bx bx-chat me-1'></i>Informasi</a>
                            </li>
                            <li class="nav-item"> <a class="nav-link" href="{{ route('login') }}"><i
                                        class='bx bx-log-in me-1'></i>Masuk</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>
        </header>
        <div class="container" style="padding-top: 100px">
            <h6 class="mb-0 text-uppercase">Card with images</h6>
            <hr />
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 row-cols-xl-4">
                <div class="col">
                    <div class="card border-primary border-bottom border-3 border-0">
                        <img src="assets/images/gallery/01.png" class="card-img-top" alt="...">
                        <div class="card-body">
                            <h5 class="card-title text-primary">Card title</h5>
                            <p class="card-text">Some quick example text to build on the card title and make up the
                                bulk of the card's conten asdasdasd asdasd asdasd asdas dasdasd t.</p>
                            <hr>
                            <div class="d-flex align-items-center gap-2">
                                <a href="javascript:;" class="btn btn-inverse-primary"><i
                                        class='bx bx-star'></i>Button</a>
                                <a href="javascript:;" class="btn btn-primary"><i
                                        class='bx bx-microphone'></i>Button</a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card border-danger border-bottom border-3 border-0">
                        <img src="assets/images/gallery/02.png" class="card-img-top" alt="...">
                        <div class="card-body">
                            <h5 class="card-title text-danger">Card title</h5>
                            <p class="card-text">Some quick example text to build on the card title and make up the
                                bulk of the card's content.</p>
                            <hr>
                            <div class="d-flex align-items-center gap-2">
                                <a href="javascript:;" class="btn btn-inverse-danger"><i
                                        class='bx bx-star'></i>Button</a>
                                <a href="javascript:;" class="btn btn-danger"><i class='bx bx-microphone'></i>Button</a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card border-success border-bottom border-3 border-0">
                        <img src="assets/images/gallery/03.png" class="card-img-top" alt="...">
                        <div class="card-body">
                            <h5 class="card-title text-success">Card title</h5>
                            <p class="card-text">Some quick example text to build on the card title and make up the
                                bulk of the card's content.</p>
                            <hr>
                            <div class="d-flex align-items-center gap-2">
                                <a href="javascript:;" class="btn btn-inverse-success"><i
                                        class='bx bx-star'></i>Button</a>
                                <a href="javascript:;" class="btn btn-success"><i
                                        class='bx bx-microphone'></i>Button</a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card border-warning border-bottom border-3 border-0">
                        <img src="assets/images/gallery/04.png" class="card-img-top" alt="...">
                        <div class="card-body">
                            <h5 class="card-title text-warning">Card title</h5>
                            <p class="card-text">Some quick example text to build on the card title and make up the
                                bulk of the card's content.</p>
                            <hr>
                            <div class="d-flex align-items-center gap-2">
                                <a href="javascript:;" class="btn btn-inverse-warning"><i
                                        class='bx bx-star'></i>Button</a>
                                <a href="javascript:;" class="btn btn-warning"><i
                                        class='bx bx-microphone'></i>Button</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!--end row-->
        </div>
        <footer class="bg-white shadow-none border-top p-2 text-center fixed-bottom">
            <p class="mb-0">Copyright © 2022. All right reserved.</p>
        </footer>
    </div>
    <!--end wrapper-->
    <!-- Bootstrap JS -->
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <!--plugins-->
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/plugins/simplebar/js/simplebar.min.js"></script>
    <script src="assets/plugins/metismenu/js/metisMenu.min.js"></script>
    <script src="assets/plugins/perfect-scrollbar/js/perfect-scrollbar.js"></script>
    <!--Password show & hide js -->
    <script>
        $(document).ready(function() {
            $("#show_hide_password a").on('click', function(event) {
                event.preventDefault();
                if ($('#show_hide_password input').attr("type") == "text") {
                    $('#show_hide_password input').attr('type', 'password');
                    $('#show_hide_password i').addClass("bx-hide");
                    $('#show_hide_password i').removeClass("bx-show");
                } else if ($('#show_hide_password input').attr("type") == "password") {
                    $('#show_hide_password input').attr('type', 'text');
                    $('#show_hide_password i').removeClass("bx-hide");
                    $('#show_hide_password i').addClass("bx-show");
                }
            });
        });
    </script>
    <!--app JS-->
    <script src="assets/js/app.js"></script>
</body>

<script>
    'undefined' === typeof _trfq || (window._trfq = []);
    'undefined' === typeof _trfd && (window._trfd = []), _trfd.push({
        'tccl.baseHost': 'secureserver.net'
    }, {
        'ap': 'cpsh-oh'
    }, {
        'server': 'p3plzcpnl509132'
    }, {
        'dcenter': 'p3'
    }, {
        'cp_id': '10399385'
    }, {
        'cp_cl': '8'
    }) // Monitoring performance to make your website faster. If you want to opt-out, please contact web hosting support.
</script>
<script src='https://img1.wsimg.com/traffic-assets/js/tccl.min.js'></script>

</html>
