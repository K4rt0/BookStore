<?php
// includes/header.php
?>
<header>
    <div class="header-area">
        <div class="main-header">
            <div class="header-top">
                <div class="container">
                    <div class="row">
                        <div class="col-xl-12">
                            <div class="d-flex justify-content-between align-items-center flex-sm">
                                <div class="header-info-left d-flex align-items-center">
                                    <!-- logo -->
                                    <div class="logo">
                                        <a href="/index.php"><img src="/assets/img/logo/logo.png" alt=""></a>
                                    </div>
                                    <!-- Search Box -->
                                    <form action="#" class="form-box">
                                        <input type="text" name="Search" placeholder="Search book by author or publisher">
                                        <div class="search-icon">
                                            <i class="ti-search"></i>
                                        </div>
                                    </form>
                                </div>
                                <div class="header-info-right d-flex align-items-center">
                                    <ul>
                                        <li><a href="#">FAQ</a></li>
                                        <li><a href="#">Track Order</a></li>
                                        <li class="shopping-card">
                                            <a href="/pages/cart.php"><img src="/assets/img/icon/cart.svg" alt=""></a>
                                        </li>
                                        <li><a href="/pages/login.php" class="btn header-btn">Sign in</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="header-bottom header-sticky">
                <div class="container">
                    <div class="row align-items-center">
                        <div class="col-xl-12">
                            <!-- logo 2 -->
                            <div class="logo2">
                                <a href="index.php"><img src="/assets/img/logo/logo.png" alt=""></a>
                            </div>
                            <!-- Main-menu -->
                            <div class="main-menu text-center d-none d-lg-block">
                                <nav>
                                    <ul id="navigation">
                                        <li><a href="/index.php">Home</a></li>
                                        <li><a href="/pages/category.php">Categories</a></li>
                                        <li><a href="/pages/about.php">About</a></li>
                                        <li><a href="/#">Pages</a>
                                            <ul class="submenu">
                                                <li><a href="/pages/login.php">Login</a></li>
                                                <li><a href="/pages/cart.php">Cart</a></li>
                                                <li><a href="/pages/checkout.php">Checkout</a></li>
                                                <li><a href="/pages/book-details.php">Book Details</a></li>
                                                <li><a href="/pages/blog-details.php">Blog Details</a></li>
                                                <li><a href="/pages/elements.php">Element</a></li>
                                            </ul>
                                        </li>
                                        <li><a href="/pages/blog.php">Blog</a></li>
                                        <li><a href="/pages/contact.php">Contact</a></li>
                                    </ul>
                                </nav>
                            </div>
                        </div>
                        <!-- Mobile Menu -->
                        <div class="col-xl-12">
                            <div class="mobile_menu d-block d-lg-none"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>