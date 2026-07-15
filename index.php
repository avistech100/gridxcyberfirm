<?php
require_once __DIR__ . "/config.php";
$allow_reg = get_site_setting('allow_self_registration', '0');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name='author' content="Gridx Team">
    <meta name="description"
        content="We at GridXCyberFirm offer comprehensive cybersecurity services that are specifically designed to safeguard businesses from the ever-changing digital threats, while simultaneously guaranteeing compliance, resilience, and peace of mind.">
    <title>Gridx Cyber Solutions</title>
    <link rel="icon" href="/static//images/logo.jpg" type="image/x-icon">
    <link rel="stylesheet" href="/static//styles/styles.css">
    <link rel="stylesheet" href="/static//styles/mobile.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <!-- AOS CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Lato:wght@300;400;700;900&family=Nunito:ital,wght@0,800;1,800&display=swap"
        rel="stylesheet">
    <!-- <script defer src="/static//scripts/scripts.js"></script> -->
    <style>
        /* Header Background and Overlay */
        header {
            background-image: linear-gradient(rgba(0, 0, 0, 0.75), rgba(0, 0, 0, 0.9)), url('https://images.unsplash.com/photo-1550751827-4bd374c3f58b?auto=format&fit=crop&w=1920&q=80') !important;
            background-size: cover !important;
            background-position: center !important;
            background-attachment: fixed !important;
        }

        /* High Contrast Header Text */
        .header-text div {
            color: #ffffff !important;
        }

        .header-text div p {
            color: #ff004f !important;
            font-weight: 700 !important;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .header-text div h1 {
            color: #ffffff !important;
            text-shadow: 0 4px 10px rgba(0, 0, 0, 0.95) !important;
        }

        .header-text .desc {
            color: #f5f5f5 !important;
            font-weight: 500 !important;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.95) !important;
            line-height: 1.6 !important;
        }

        /* Explore Services Button Contrast */
        .header-text a {
            color: #ffffff !important;
            border: 2px solid #ff004f !important;
            background-color: rgba(255, 0, 79, 0.2) !important;
            box-shadow: 0 4px 15px rgba(255, 0, 79, 0.35) !important;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5) !important;
        }

        .header-text a:hover {
            background-color: #ff004f !important;
            color: #ffffff !important;
            box-shadow: 0 4px 20px rgba(255, 0, 79, 0.6) !important;
        }

        /* Custom contrast for contact form and other elements */
        .contact-right form button {
            color: #ffffff !important;
            border: 2px solid var(--secondary) !important;
            font-weight: 700 !important;
        }

        /* Nav auth buttons */
        .nav-auth-btns {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
        }
        .nav-btn {
            display: inline-block;
            padding: 7px 16px;
            border-radius: 20px;
            font-size: 0.88rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
            white-space: nowrap;
        }
        .nav-btn-login {
            color: #fff;
            border: 1.5px solid rgba(255,255,255,0.6);
            background: transparent;
        }
        .nav-btn-login:hover {
            background: rgba(255,255,255,0.15);
        }
        .nav-btn-signup {
            color: #1e2f6f;
            background: #ffffff;
            border: 1.5px solid #ffffff;
        }
        .nav-btn-signup:hover {
            background: #e8ecf8;
        }
        @media (max-width: 768px) {
            .nav-auth-btns { display: none; }
        }
        
        .contact-right form button:hover {
            border-color: var(--primary) !important;
            background-color: var(--primary) !important;
        }
    </style>
</head>
<body>
    <div class="modal">
        <div class="loader">
        </div>
        <img src="/static/images/logo.png" alt="Gridx logo">
    </div>
    <nav>
        <img loading="lazy" src="/static//images/logo.png" alt="Gridx Logo">
        <ul id="li">
            <img id="cancel" loading="lazy" src="/static//images/SVGs/cancel.svg" alt="cancel icon" width="30" height="30" />
            <li><a href="#">Home</a></li>
            <li><a href="#about">About</a></li>
            <li><a href="#services">Our Services</a></li>
            <li><a href="#team">Team</a></li>
            <li><a href="#faq">FAQ ?</a></li>
            <li><a href="#contact">Contact us</a></li>
        </ul>
        <div class="nav-auth-btns">
            <a href="/login.php" class="nav-btn nav-btn-login">Login</a>
            <?php if ($allow_reg === '1'): ?>
            <a href="/signup.php" class="nav-btn nav-btn-signup">Sign Up</a>
            <?php endif; ?>
        </div>
        <div class="display">
            <img loading="lazy" id="li-display" src="/static//images/SVGs/hambuger.svg" alt="list icon" width="30" height="30">
        </div>
    </nav>
    <header>
        <div class="layout">
            <div class="header-text">
                <div  data-aos="fade-up" data-aos-delay="500">
                    <p>Personal and Business Services</p>
                    <H1>Reclaim <br>Your digital asset</H1>
                </div>
                <p class="desc"  data-aos="fade-right" data-aos-delay="500">Our goal at Gridx Cybersecurity Solutions is to shield companies of all sizes from the
                    changing risks associated with the digital era. Our company was founded on the idea that effective
                    cybersecurity should be proactive, scalable, and easily accessible. We provide customized solutions
                    that go beyond basic defense and instead create resilience.</p>
                <a href="#services">
                    Explore our Services
                    <!-- <img src="/images/SVGs/forward.svg" alt="icon"> -->
                </a>

            </div>
            <div class="vid">
                <iframe loading="lazy" width="315" height="560" src="https://www.youtube.com/embed/yhmgUs73Jb8"
                    title="Message us now on WhatsApp for fast recovery 👉 +1 (559) 831-7210" frameborder="0"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                    referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
            </div>
        </div>
    </header>
    <main>
        <!------------------About Section----------------------------------------->
        <div id="about" class="about">
            <div class="container">
                <h2  data-aos="fade-up" data-aos-delay="500">About Us</h2>
                <div class="row">
                    <div class="about-col-1"  data-aos="fade-right" data-aos-delay="500">
                        <img loading="lazy" src="https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?auto=format&fit=crop&w=800&q=80" alt="Hacker Image">
                    </div>
                    <div class="about-coll-2" data-aos="fade-left" data-aos-delay="500">
                        <h3>Expertise In Data Recovery and Cybersecurity</h3>
                        <p>Our end-to-end cybersecurity services at Gridx Cyber Firm are intended to protect businesses
                            against contemporary online dangers. Our company offers complete protection plans that
                            incorporate cutting-edge technology, industry best practices, and knowledgeable advice.</p>

                        <div class="tabs-title">
                            <p class="tab-links active-link" onclick="openTab('skills')">Why chose Us</p>
                            <p class="tab-links" onclick="openTab('Expirence')">Mission</p>
                            <p class="tab-links" onclick="openTab('Education')">Vission</p>
                        </div>
                        <div class="tabs-contents active-tab" id="skills" data-aos="fade-up" data-aos-delay="500">
                            <p>In today's rapidly evolving digital landscape, cybersecurity is no longer a luxury it's a necessity. At Gridxcyberfirm, we understand the importance of protecting your business from ever-increasing cyber threats.</p>
                        </div>
                        <div class="tabs-contents" id="Expirence">
                            <p>Gridx Cyber Firm's goal is to help individuals and businesses succeed in a secure online environment. We provide creative, effective, and personalized cybersecurity solutions that safeguard our clients' vital resources and information while also promoting resilience, trust, and confidence in the face of growing threats.</p>
                        </div>
                        <div class="tabs-contents" id="Education">
                            <p>Our aim is to be the trusted cybersecurity leader, known for our knowledge, creativity, and commitment to excellence. We strive to build a safer digital landscape for all, through cutting-edge solutions, collaborative collaborations, and an unwavering pursuit of security and integrity.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!------------------Services Section----------------------------------------->
        <div id="services" class="services">
            <div class="container">
                <h2 data-aos="fade-up" data-aos-delay="500">Our Services</h2>
                <div class="services-list">
                    <div class='service-details' data-aos="fade-up" data-aos-delay="500">
                        <div>
                            <i class="bi bi-eye-fill"></i>
                            <h2>Spy on Partner</h2>
                            <span>
                                <span>Keep an eye on suspicious activity and find covert communications without drawing
                                    attention to yourself.</span>
                                <a id="t-link" href="https://t.me/Gridxcyberfirm"><img alt="Telegram link"
                                        loading="lazy" src="/static//images/SVGs/telegram.svg"></a>
                            </span>
                            <a href="#">Learn More</a>
                        </div>
                        <img loading="lazy" src="https://images.unsplash.com/photo-1639762681485-074b7f938ba0?auto=format&fit=crop&w=600&q=80" alt="Laptop secure">
                    </div>
                    <div class='service-details' data-aos="fade-up" data-aos-delay="500">
                        <div>
                            <i class="bi bi-pin-map-fill"></i>
                            <h2>Tracking</h2>
                            <span>
                                <span>Get real-time GPS tracking and information on where people or things are.</span>
                                <a id="t-link" href="https://t.me/Gridxcyberfirm"><img alt="Telegram link"
                                        loading="lazy" src="/static//images/SVGs/telegram.svg"></a>
                            </span>
                            <a href="#">Learn More</a>
                        </div>
                        <img loading="lazy" src="https://images.unsplash.com/photo-1508847154043-be12a62861c1?auto=format&fit=crop&w=600&q=80" alt=" Laptop Secure">
                    </div>
                    <div class='service-details' data-aos="fade-up" data-aos-delay="500">
                        <div>
                            <i class="bi bi-box-seam-fill"></i>
                            <h2>Lost property</h2>
                            <span>
                                <span>Investigate through and get back stolen or lost gadgets and digital
                                    property.</span>
                                <a id="t-link" href="https://t.me/Gridxcyberfirm"><img alt="Telegram link"
                                        loading="lazy" src="/static//images/SVGs/telegram.svg"></a>
                            </span>
                            <a href="#">Learn More</a>
                        </div>
                        <img loading="lazy" src="https://images.unsplash.com/photo-1563986768609-322da13575f3?auto=format&fit=crop&w=600&q=80" alt="padlock and laptop">
                    </div>
                    <div class='service-details' data-aos="fade-up" data-aos-delay="500">
                        <div>
                            <i class="bi bi-virus"></i>
                            <h2>Anti Virus Support</h2>
                            <span>
                                <span>Keep your device safe from new cyber dangers, viruses, malware, and
                                    spyware.</span>
                                <a id="t-link" href="https://t.me/Gridxcyberfirm"><img alt="Telegram link"
                                        loading="lazy" src="/static//images/SVGs/telegram.svg"></a>
                            </span>
                            <a href="#">Learn More</a>
                        </div>
                        <img class="ser-img" loading="lazy" src="https://images.unsplash.com/photo-1614064641938-3bbee52942c7?auto=format&fit=crop&w=600&q=80" alt="padlock">
                    </div>
                    <h3 data-aos="fade-up" data-aos-delay="500">CRYPTO RECOVERY</h3>
                    <div class='service-details' data-aos="fade-up" data-aos-delay="500">
                        <div>
                            <i class="bi bi-coin"></i>
                            <h2>Coinbase Recovery</h2>
                            <span>
                                <span>Regain access to your CoinBase account and reclaim any lost bitcoin
                                    holdings.</span>
                                <a id="t-link" href="https://t.me/Gridxcyberfirm"><img alt="Telegram link"
                                        loading="lazy" src="/static//images/SVGs/telegram.svg"></a>
                            </span>
                            <a href="#">Learn More</a>
                        </div>
                        <img class="ser-img" loading="lazy" src="https://images.unsplash.com/photo-1614064641938-3bbee52942c7?auto=format&fit=crop&w=600&q=80" alt="padlock">
                    </div>
                    <div class='service-details' data-aos="fade-up" data-aos-delay="500">
                        <div>
                            <i class="bi bi-currency-bitcoin"></i>
                            <h2>Bitcoin Rcovery</h2>
                            <span>
                                <span>Recover stolen or misplaced cryptocurrency from compromised wallets and
                                    exchanges.</span>
                                <a id="t-link" href="https://t.me/Gridxcyberfirm"><img alt="Telegram link"
                                        loading="lazy" src="/static//images/SVGs/telegram.svg"></a>
                            </span>
                            <a href="#">Learn More</a>
                        </div>
                        <img class="ser-img" loading="lazy" src="https://images.unsplash.com/photo-1614064641938-3bbee52942c7?auto=format&fit=crop&w=600&q=80" alt="padlock">
                    </div>
                    <div class='service-details' data-aos="fade-up" data-aos-delay="500">
                        <div>
                            <i class="bi bi-wallet"></i>
                            <h2>Lost Funds</h2>
                            <span>
                                <span>Recover funds that have been misappropriated as a result of fraudulent activities,
                                    online scams, or unsuccessful digital transactions.</span>
                                <a id="t-link" href="https://t.me/Gridxcyberfirm"><img alt="Telegram link"
                                        loading="lazy" src="/static//images/SVGs/telegram.svg"></a>
                            </span>
                            <a href="#">Learn More</a>
                        </div>
                        <img class="ser-img" loading="lazy" src="https://images.unsplash.com/photo-1614064641938-3bbee52942c7?auto=format&fit=crop&w=600&q=80" alt="padlock">
                    </div>
                    <div class='service-details' data-aos="fade-up" data-aos-delay="500">
                        <div>
                            <i class="bi bi-bag-check-fill"></i>
                            <h2>Ebay Account Recovery</h2>
                            <span>
                                <span>Regain access to your eBay account and resolve all unauthorized
                                    transactions.</span>
                                <a id="t-link" href="https://t.me/Gridxcyberfirm"><img alt="Telegram link"
                                        loading="lazy" src="/static//images/SVGs/telegram.svg"></a>
                            </span>
                            <a href="#">Learn More</a>
                        </div>
                        <img class="ser-img" loading="lazy" src="https://images.unsplash.com/photo-1614064641938-3bbee52942c7?auto=format&fit=crop&w=600&q=80" alt="padlock">
                    </div>
                    <div class='service-details' data-aos="fade-up" data-aos-delay="500">
                        <div>
                            <i class="bi bi-controller"></i>
                            <h2>Game Account Recovery</h2>
                            <span>
                                <span>Revive lost or banned gaming accounts across various platforms and games.</span>
                                <a id="t-link" href="https://t.me/Gridxcyberfirm"><img alt="Telegram link"
                                        loading="lazy" src="/static//images/SVGs/telegram.svg"></a>
                            </span>
                            <a href="#">Learn More</a>
                        </div>
                        <img class="ser-img" loading="lazy" src="https://images.unsplash.com/photo-1614064641938-3bbee52942c7?auto=format&fit=crop&w=600&q=80" alt="padlock">
                    </div>
                    <h3 data-aos="fade-up" data-aos-delay="500">Account Recovery</h3>
                    <div class='service-details' data-aos="fade-up" data-aos-delay="500">
                        <div>
                            <i class="bi bi-envelope-at-fill"></i>
                            <h2>Yahoo Email Recovery</h2>
                            <span>
                                <span>Regain access to your lost or compromised Yahoo email accounts quickly and in the
                                    most secured way posible.</span>
                                <a id="t-link" href="https://t.me/Gridxcyberfirm"><img alt="Telegram link"
                                        loading="lazy" src="/static//images/SVGs/telegram.svg"></a>
                            </span>
                            <a href="#">Learn More</a>
                        </div>
                        <img class="ser-img" loading="lazy" src="https://images.unsplash.com/photo-1614064641938-3bbee52942c7?auto=format&fit=crop&w=600&q=80" alt="padlock">
                    </div>
                    <div class='service-details' data-aos="fade-up" data-aos-delay="500">
                        <div>
                            <i class="bi bi-google"></i>
                            <h2>Gmail Recovery</h2>
                            <span>
                                <span>We help in recovering access to Gmail accounts.</span>
                                <a id="t-link" href="https://t.me/Gridxcyberfirm"><img alt="Telegram link"
                                        loading="lazy" src="/static//images/SVGs/telegram.svg"></a>
                            </span>
                            <a href="#">Learn More</a>
                        </div>
                        <img class="ser-img" loading="lazy" src="https://images.unsplash.com/photo-1614064641938-3bbee52942c7?auto=format&fit=crop&w=600&q=80" alt="padlock">
                    </div>
                    <div class='service-details' data-aos="fade-up" data-aos-delay="500">
                        <div>
                            <i class="bi bi-facebook"></i>
                            <h2>Facebook Recovery</h2>
                            <span>
                                <span>Get back into Facebook accounts that have been compromised or locked.</span>
                                <a id="t-link" href="https://t.me/Gridxcyberfirm"><img alt="Telegram link"
                                        loading="lazy" src="/static//images/SVGs/telegram.svg"></a>
                            </span>
                            <a href="#">Learn More</a>
                        </div>
                        <img class="ser-img" loading="lazy" src="https://images.unsplash.com/photo-1614064641938-3bbee52942c7?auto=format&fit=crop&w=600&q=80" alt="padlock">
                    </div>
                    <div class='service-details' data-aos="fade-up" data-aos-delay="500">
                        <div>
                            <i class="bi bi-twitter"></i>
                            <h2>X recovery</h2>
                            <span>
                                <span>We assist you in getting your X (previously Twitter) accounts back.</span>
                                <a id="t-link" href="https://t.me/Gridxcyberfirm"><img alt="Telegram link"
                                        loading="lazy" src="/static//images/SVGs/telegram.svg"></a>
                            </span>
                            <a href="#">Learn More</a>
                        </div>
                        <img class="ser-img" loading="lazy" src="https://images.unsplash.com/photo-1614064641938-3bbee52942c7?auto=format&fit=crop&w=600&q=80" alt="padlock">
                    </div>
                    <div class='service-details' data-aos="fade-up" data-aos-delay="500">
                        <div>
                            <i class="bi bi-instagram"></i>
                            <h2>Instagram Recovery</h2>
                            <span>
                                <span>Easily recover your compromised or lost Instagram accounts.</span>
                                <a id="t-link" href="https://t.me/Gridxcyberfirm"><img alt="Telegram link"
                                        loading="lazy" src="/static//images/SVGs/telegram.svg"></a>
                            </span>
                            <a href="#">Learn More</a>
                        </div>
                        <img class="ser-img" loading="lazy" src="https://images.unsplash.com/photo-1614064641938-3bbee52942c7?auto=format&fit=crop&w=600&q=80" alt="padlock">
                    </div>
                    <div class='service-details' data-aos="fade-up" data-aos-delay="500">
                        <div>
                            <i class="bi bi-linkedin"></i>
                            <h2>LinkedIn Recovery</h2>
                            <span>
                                <span>Support for restoring company pages and LinkedIn profiles.</span>
                                <a id="t-link" href="https://t.me/Gridxcyberfirm"><img alt="Telegram link"
                                        loading="lazy" src="/static//images/SVGs/telegram.svg"></a>
                            </span>
                            <a href="#">Learn More</a>
                        </div>
                        <img class="ser-img" loading="lazy" src="https://images.unsplash.com/photo-1614064641938-3bbee52942c7?auto=format&fit=crop&w=600&q=80" alt="padlock">
                    </div>


                    <h3>Device Services</h3>


                    <div class='service-details'>
                        <div>
                            <i class="bi bi-reception-4"></i>
                            <h2>Carrier Unlock</h2>
                            <span>
                                <span>Unlock mobile devices' network limits for freedom and worldwide use.</span>
                                <a id="t-link" href="https://t.me/Gridxcyberfirm"><img alt="Telegram link"
                                        loading="lazy" src="/static//images/SVGs/telegram.svg"></a>
                            </span>
                            <a href="#">Learn More</a>
                        </div>
                        <img class="ser-img" loading="lazy" src="https://images.unsplash.com/photo-1614064641938-3bbee52942c7?auto=format&fit=crop&w=600&q=80" alt="padlock">
                    </div>
                    <div class='service-details'>
                        <div>
                            <i class="bi bi-cloud-arrow-up-fill"></i>
                            <h2>iCloud Unlock</h2>
                            <span>
                                <span>Regain safe access to your Apple devices by getting past the iCloud activation
                                    lock.</span>
                                <a id="t-link" href="https://t.me/Gridxcyberfirm"><img alt="Telegram link"
                                        loading="lazy" src="/static//images/SVGs/telegram.svg"></a>
                            </span>
                            <a href="#">Learn More</a>
                        </div>
                        <img class="ser-img" loading="lazy" src="https://images.unsplash.com/photo-1614064641938-3bbee52942c7?auto=format&fit=crop&w=600&q=80" alt="padlock">
                    </div>
                    <div class='service-details'>
                        <div>
                            <i class="bi bi-unlock-fill"></i>
                            <h2>Device Bybass</h2>
                            <span>
                                <span>Quickly and safely get around locks and security measures on a variety of
                                    electronic gadgets.</span>
                                <a id="t-link" href="https://t.me/Gridxcyberfirm"><img alt="Telegram link"
                                        loading="lazy" src="/static//images/SVGs/telegram.svg"></a>
                            </span>
                            <a href="#">Learn More</a>
                        </div>
                        <img class="ser-img" loading="lazy" src="https://images.unsplash.com/photo-1614064641938-3bbee52942c7?auto=format&fit=crop&w=600&q=80" alt="padlock">
                    </div>
                    <div class='service-details'>
                        <div>
                            <i class="bi bi-microsoft"></i>
                            <h2>Microsoft Outlook <br>Account Recovery</h2>
                            <span>
                                <span>Quickly and safely retrieve your deleted or compromised Outlook and Hotmail email
                                    accounts.</span>
                                <a id="t-link" href="https://t.me/Gridxcyberfirm"><img alt="Telegram link"
                                        loading="lazy" src="/static//images/SVGs/telegram.svg"></a>
                            </span>
                            <a href="#">Learn More</a>
                        </div>
                        <img class="ser-img" loading="lazy" src="https://images.unsplash.com/photo-1614064641938-3bbee52942c7?auto=format&fit=crop&w=600&q=80" alt="padlock">
                    </div>
                    <div class='service-details'>
                        <div>
                            <i class="bi bi-apple"></i>
                            <h2>Apple ID Recovery</h2>
                            <span>
                                <span>Restore your Apple ID accounts' functionality, including email, purchases, and
                                    services.</span>
                                <a id="t-link" href="https://t.me/Gridxcyberfirm"><img alt="Telegram link"
                                        loading="lazy" src="//static/images/SVGs/telegram.svg"></a>
                            </span>
                            <a href="#">Learn More</a>
                        </div>
                        <img class="ser-img" loading="lazy" src="https://images.unsplash.com/photo-1614064641938-3bbee52942c7?auto=format&fit=crop&w=600&q=80" alt="padlock">
                    </div>
                    <div class='service-details'>
                        <div>
                            <i class="bi bi-snapchat"></i>
                            <h2>Snapchat Recovery</h2>
                            <span>
                                <span>support for restoring company pages and LinkedIn profiles.</span>
                                <a id="t-link" href="https://t.me/Gridxcyberfirm"><img alt="Telegram link"
                                        loading="lazy" src="/static//images/SVGs/telegram.svg"></a>
                            </span>
                            <a href="#">Learn More</a>
                        </div>
                        <img class="ser-img" loading="lazy" src="https://images.unsplash.com/photo-1614064641938-3bbee52942c7?auto=format&fit=crop&w=600&q=80" alt="padlock">
                    </div>
                    <div class='service-details'>
                        <div>
                            <i class="bi bi-tiktok"></i>
                            <h2>Tiktok Recovery</h2>
                            <span>
                                <span>Get your videos and followers back by regaining access to your TikTok
                                    account.</span>
                                <a id="t-link" href="https://t.me/Gridxcyberfirm"><img alt="Telegram link"
                                        loading="lazy" src="/static//images/SVGs/telegram.svg"></a>
                            </span>
                            <a href="#">Learn More</a>
                        </div>
                        <img class="ser-img" loading="lazy" src="https://images.unsplash.com/photo-1614064641938-3bbee52942c7?auto=format&fit=crop&w=600&q=80" alt="padlock">
                    </div>
                    <div class='service-details'>
                        <div>
                            <i class="bi bi-pinterest"></i>
                            <h2>Pintrest Recovery</h2>
                            <span>
                                <span>Protect your stored pins and Pinterest account from unwanted access.</span>
                                <a id="t-link" href="https://t.me/Gridxcyberfirm"><img alt="Telegram link"
                                        loading="lazy" src="/static//images/SVGs/telegram.svg"></a>
                            </span>
                            <a href="#">Learn More</a>
                        </div>
                        <img class="ser-img" loading="lazy" src="https://images.unsplash.com/photo-1614064641938-3bbee52942c7?auto=format&fit=crop&w=600&q=80" alt="padlock">
                    </div>
                    <div class='service-details'>
                        <div>
                            <i class="bi bi-phone"></i>
                            <h2>IMEI REPAIR</h2>
                            <span>
                                <span>To fix network and service problems, fix or replace the IMEI number on your
                                    device.</span>
                                <a id="t-link" href="https://t.me/Gridxcyberfirm"><img alt="Telegram link"
                                        loading="lazy" src="/static//images/SVGs/telegram.svg"></a>
                            </span>
                            <a href="#">Learn More</a>
                        </div>
                        <img class="ser-img" loading="lazy" src="https://images.unsplash.com/photo-1614064641938-3bbee52942c7?auto=format&fit=crop&w=600&q=80" alt="padlock">
                    </div>
                    <div class='service-details'>
                        <div>
                            <i class="bi bi-database-fill-check"></i>
                            <h2>Data Recovery</h2>
                            <span>
                                <span>Easily retrieve your hacked or lost X (Twitter) accounts.</span>
                                <a id="t-link" href="https://t.me/Gridxcyberfirm"><img alt="Telegram link"
                                        loading="lazy" src="/static//images/SVGs/telegram.svg"></a>
                            </span>
                            <a href="#">Learn More</a>
                        </div>
                        <img class="ser-img" loading="lazy" src="https://images.unsplash.com/photo-1614064641938-3bbee52942c7?auto=format&fit=crop&w=600&q=80" alt="padlock">
                    </div>
                    <div class='service-details'>
                        <div>
                            <i class="bi bi-shield-fill-check"></i>
                            <h2>Penetration Testing</h2>
                            <span>
                                <span>Find security holes in your network or website before hackers do.</span>
                                <a id="t-link" href="https://t.me/Gridxcyberfirm"><img alt="Telegram link"
                                        loading="lazy" src="/static//images/SVGs/telegram.svg"></a>
                            </span>
                            <a href="#">Learn More</a>
                        </div>
                        <img class="ser-img" loading="lazy" src="https://images.unsplash.com/photo-1614064641938-3bbee52942c7?auto=format&fit=crop&w=600&q=80" alt="padlock">
                    </div>
                    <div class='service-details'>
                        <div>
                            <i class="bi bi-search"></i>
                            <h2>Digital Forensics</h2>
                            <span>
                                <span>Examine and evaluate digital evidence for security and legal reasons.</span>
                                <a id="t-link" href="https://t.me/Gridxcyberfirm"><img alt="Telegram link"
                                        loading="lazy" src="/static//images/SVGs/telegram.svg"></a>
                            </span>
                            <a href="#">Learn More</a>
                        </div>
                        <img class="ser-img" loading="lazy" src="https://images.unsplash.com/photo-1614064641938-3bbee52942c7?auto=format&fit=crop&w=600&q=80" alt="padlock">
                    </div>
                    <div class='service-details'>
                        <div>
                            <i class="bi bi-wallet2"></i>
                            <h2>PayPal Recovery</h2>
                            <span>
                                <span>Recover money and access from hacked PayPal accounts in a secure and expert
                                    manner.</span>
                                <a id="t-link" href="https://t.me/Gridxcyberfirm"><img alt="Telegram link"
                                        loading="lazy" src="/static//images/SVGs/telegram.svg"></a>
                            </span>
                            <a href="#">Learn More</a>
                        </div>
                        <img class="ser-img" loading="lazy" src="https://images.unsplash.com/photo-1614064641938-3bbee52942c7?auto=format&fit=crop&w=600&q=80" alt="padlock">
                    </div>
                    <div class='service-details'>
                        <div>
                            <i class="bi bi-bank"></i>
                            <h2>Bank Accouunt Recovery</h2>
                            <span>
                                <span>support for regaining bank account access following fraud or illegal
                                    access.</span>
                                <a id="t-link" href="https://t.me/Gridxcyberfirm"><img alt="Telegram link"
                                        loading="lazy" src="/static//images/SVGs/telegram.svg"></a>
                            </span>
                            <a href="#">Learn More</a>
                        </div>
                        <img class="ser-img" loading="lazy" src="https://images.unsplash.com/photo-1614064641938-3bbee52942c7?auto=format&fit=crop&w=600&q=80" alt="padlock">
                    </div>
                    <div class='service-details'>
                        <div>
                            <i class="bi bi-credit-card-fill"></i>
                            <h2>Credit Card Support</h2>
                            <span>
                                <span>Assistance for victims of credit card fraud, including guidance on recovery and
                                    investigation.</span>
                                <a id="t-link" href="https://t.me/Gridxcyberfirm"><img alt="Telegram link"
                                        loading="lazy" src="/static//images/SVGs/telegram.svg"></a>
                            </span>
                            <a href="#">Learn More</a>
                        </div>
                        <img class="ser-img" loading="lazy" src="https://images.unsplash.com/photo-1614064641938-3bbee52942c7?auto=format&fit=crop&w=600&q=80" alt="padlock">
                    </div>
                </div>
            </div>
        </div>
        <!----------------------------- Portfolio ---------------------------------->
        <div id="team" class="team">
            <div class="container">
                <h2 data-aos="fade-up" data-aos-delay="500">Our Team</h2>
                <div class="team-list">
                    <div data-aos="fade-right" data-aos-delay="500" class="team-container">
                        <img loading="lazy" src="https://images.unsplash.com/photo-1560250097-0b93528c311a?auto=format&fit=crop&w=300&q=80" alt="CEO profile">
                        <div>
                            <section>
                                <h3>David Lawson</h3>
                                <p>CEO</p>
                            </section>
                            <p>Our team is led by an expert in cybersecurity and digital asset recovery, who guarantees
                                that each client receives the highest quality of service.</p>
                        </div>
                    </div>
                    <div class="team-container" data-aos="fade-left" data-aos-delay="500">
                        <img loading="lazy" src="https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?auto=format&fit=crop&w=300&q=80" alt="Personal Assistant to the CEO profile">
                        <div>
                            <section>
                                <h3>James Carter</h3>
                                <p>Product Manager</p>
                            </section>
                            <p>Promoting innovation and organizing methods to effectively recover important digital
                                accounts and lift carrier limitations</p>
                        </div>
                    </div>
                    <div class="team-container" data-aos="fade-right" data-aos-delay="500">
                        <img loading="lazy" src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=300&q=80"
                            alt="senior cyber security annalyst profile">
                        <div>
                            <section>
                                <h3>Ethan Clarke</h3>
                                <p>Security Analyst</p>
                            </section>
                            <p>Investigates suspicious activity, escalates incidents, and monitors security alerts.</p>
                        </div>
                    </div>
                    <div class="team-container" data-aos="fade-left" data-aos-delay="500">
                        <img loading="lazy" src="https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&w=300&q=80" alt="Penetration tester profile">
                        <div>
                            <section>
                                <h3>Sophia Reynolds</h3>
                                <p>Incident Responder</p>
                            </section>
                            <p>Handles security breaches, looks into assaults, and fixes systems that have been hacked.
                            </p>
                        </div>
                    </div>
                    <div class="team-container" data-aos="fade-right" data-aos-delay="500">
                        <img loading="lazy" src="https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=300&q=80" alt="Senior Software developer profile">
                        <div>
                            <section>
                                <h3>Daniel Stone</h3>
                                <p>Penetration Tester</p>
                            </section>
                            <p>Simulates real-world attacks to identify vulnerabilities.</p>
                        </div>
                    </div>
                    <div class="team-container" data-aos="fade-left" data-aos-delay="500">
                        <img loading="lazy" src="https://images.unsplash.com/photo-1580489944761-15a19d654956?auto=format&fit=crop&w=300&q=80" alt="Senior Software developer profile">
                        <div>
                            <section>
                                <h3>Olivia Brooks</h3>
                                <p>Network Security Engineer</p>
                            </section>
                            <p>Designs and maintains secure networks (firewalls, VPNs, IDS/IPS).</p>
                        </div>
                    </div>
                    <div class="team-container" data-aos="fade-right" data-aos-delay="500">
                        <img loading="lazy" src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?auto=format&fit=crop&w=300&q=80" alt="Senior Software developer profile">
                        <div>
                            <section>
                                <h3>John Packer</h3>
                                <p>IT consultant</p>
                            </section>
                            <p>Constantly making sure that customers and organizations get and adhere to the most recent
                                security requirements for protecting their assets or business</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div id="faq" class="faq">
            <h2 data-aos="fade-up" data-aos-delay="500">Frequently Asked Questions?</h2>
            <div class="faq-container container">
                <img loading="lazy" data-aos="fade-right" data-aos-delay="500" src="https://images.unsplash.com/photo-1550751827-4bd374c3f58b?auto=format&fit=crop&w=800&q=80" alt="cyber image">
                <div data-aos="fade-left" data-aos-delay="500">
                    <details>
                        <summary>How much time does it take to unlock an account?</summary>
                        <p>Depending on the account status and the severity of the carrier limitations, our unlocking services usually take three to seven hours. We keep you informed at every stage.</p>
                    </details>
                    <details>
                        <summary>Is my personal information safeguarded during the recovering process?</summary>
                        <p>Absolutely. We adhere to strong security procedures to ensure that all of your data is kept confidential and secure. Your privacy is our top priority.</p>
                    </details>
                    <details>
                        <summary>What kinds of digital accounts can you assist recover?</summary>
                        <p>We specialize in regaining carrier or service provider-locked accounts, such as mobile network accounts, email accounts, and social media identities.</p>
                    </details>
                    <details>
                        <summary>Do you offer help after the account has been unlocked?</summary>
                        <p>Yes, we provide continuous assistance to help you safeguard your account and avoid future lockouts, including advice on best security practices.</p>
                    </details>
                    <details>
                        <summary>How can I contact assistance if I have any further questions?</summary>
                        <p>You can contact our support team using the contact form, email, or live chat feature on our website. We are available 24 hours a day, seven days a week.</p>
                    </details>
                    <details>
                        <summary>Which payment options do you accept?</summary>
                        <p>To make the procedure more comfortable for you, we accept a variety of payment methods such as major credit cards, PayPal, cryptocurrency, and bank transfers.</p>
                    </details>
                </div>
            </div>
        </div>
        <div id="review" class="review" data-aos="fade-up" data-aos-delay="500">
            <div class="container">
            <h2>Customer Review</h2>
            <div class="carousel-container">
                <div class="carousel">
                    <div class="carousel-item active">
                        <img loading="lazy" src="https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?auto=format&fit=crop&w=150&q=80" alt="Image 1">
                        <div class="carousel-item-description">
                            <h2>Ahmed Ali </h2>
                            <p>GridXCyberFirm helped me recover my cloned phone. They secured my accounts and restored
                                my privacy. A lifesaver!</p>
                            <a href="">⭐⭐⭐⭐⭐</a>
                        </div>
                    </div>
                    <div class="carousel-item">
                        <img loading="lazy" src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=150&q=80" alt="Image 2">
                        <div class="carousel-item-description">
                            <h2>Sarah Mitchell</h2>
                            <p>I lost money to an online scam, but thanks to GridXCyberFirm, I was able to trace the
                                fraud and recover my funds. Professional and effective</p>
                            <a href="">⭐⭐⭐⭐⭐</a>
                        </div>
                    </div>
                    <div class="carousel-item">
                        <img loading="lazy" src="https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=150&q=80" alt="Image 3">
                        <div class="carousel-item-description">
                            <h2>James Anderson</h2>
                            <p>During my divorce, I suspected hidden conversations and infidelity. GridXCyberFirm
                                provided digital proof that stood in court. Highly discreet and reliable</p>
                            <a href="">⭐⭐⭐⭐⭐</a>
                        </div>
                    </div>
                    <div class="carousel-item">
                        <img loading="lazy" src="https://images.unsplash.com/photo-1544005313-94ddf0286df2?auto=format&fit=crop&w=150&q=80" alt="Image 4">
                        <div class="carousel-item-description">
                            <h2>Mr Carter and family</h2>
                            <p>Our business network was under attack, and GridXCyberFirm acted immediately. They shut
                                down the threat and protected our data</p>
                            <a href="">⭐⭐⭐⭐⭐</a>
                        </div>
                    </div>
                    <div class="carousel-item">
                        <img loading="lazy" src="https://images.unsplash.com/photo-1539571696357-5a69c17a67c6?auto=format&fit=crop&w=150&q=80" alt="Image 5">
                        <div class="carousel-item-description">
                            <h2>David Singh</h2>
                            <p>They uncovered spyware on my phone that was leaking sensitive information. Now my device
                                is completely secure.</p>
                            <a href="">⭐⭐⭐⭐⭐</a>
                        </div>
                    </div>
                </div>
                <button class="prev-btn"><img loading="lazy" src="/static//images/SVGs/backward.svg" alt=""></button>
                <button class="next-btn"><img loading="lazy" src="/static//images/SVGs/forward.svg" alt=""></button>
                <div class="dots-container"></div>
            </div>
        </div>
        </div>
        <div id="contact" class="contact">
            <div class="container">
                <div class="row">
                    <div class="contact-left" data-aos="fade-left" data-aos-delay="500">
                        <h2 class="sub-title">Contact Us</h2>
                        <span><img loading="lazy" src="/static//images/SVGs/telegram.svg" alt="Phone">
                            <h4>info@gridxcyberfirm.com</h4>
                        </span>
                        <span><img loading="lazy" src="/static//images/SVGs/phone.svg"
                                alt="https://wa.me/message/UM4UXCT77QFOE1">
                            <h4>+1 (385) 338-9337</h4>
                        </span>
                        <div class="social-icons">
                            <a target="_blank" href="https://www.facebook.com/share/1K5u9spPwj/?mibextid=wwXIfr"><img loading="lazy"
                                    src="/static//images/SVGs/facebook.svg" alt="Facebook logo"></a>
                            <a target="_blank"
                                href="https://www.instagram.com/gridxcyberfirm?igsh=aWJsM3psOHl3enA0"><img loading="lazy" src="/static//images/SVGs/instagram.svg" alt="Instagram logo"></a>
                            <a target="_blank" href="https://t.me/Gridxcyberfirm"><img src="/static/images/SVGs/telegram.svg"
                                    alt="Telegram logo"></a>
                            <a target="_blank" href="https://www.youtube.com/@GRIDXCyberFirm"><img loading="lazy"
                                    src="/static//images/SVGs//youtube.svg" alt=" Youtube Logo"></a>
                            <a target="_blank"
                                href="https://www.tiktok.com/@user8827760818518?_t=ZS-8zIS0J1OVlp&_r=1"><img loading="lazy"
                                    src="/static//images/SVGs/tiktok.svg" alt="Tiktok Logo"></a>
                            <a target="_blank" href="https://wa.me/message/UM4UXCT77QFOE1"><img loading="lazy"
                                    src="/static//images/SVGs/whatsapp.svg" alt="WhatsApp logo"></a>
                        </div>
                    </div>
                    <div class="contact-right">
                        <form action="#" method="post" data-aos="fade-right" data-aos-delay="500">
                            <div>
                                <span><img loading="lazy" src="/static//images/SVGs/user.svg" alt=""> <label for="name">Name </label></span>
                                <input type="text" id="name">
                            </div>
                            <div>
                                <span><img loading="lazy" src="/static//images/SVGs/email.svg" alt="email logo"> <label for="email">Email
                                    </label></span>
                                <input type="email" id="email">
                            </div>
                            <div>
                                <span><img loading="lazy" src="/static//images/SVGs/comment.svg" alt="message icon"> <label for="email">Message
                                    </label></span>
                                <textarea name="message" rows="4" id="message"
                                    placeholder="Enter Your Message"></textarea>
                            </div>
                            <div id="checkbox">
                                <input type="checkbox" name="purose" id="subscribe" value="Website Suggestion">
                                <label for="subscribe">Please subscribe to our monthly newsletters</label>
                            </div>
                            <div>
                                <button type="submit">Send Message</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <footer>
        <div class="footercont">
            <div class="first">
                <h2>Gridxcyberfirm</h2>
                <p>Our goal at Gridxcyberfirm is to shield companies of all sizes from the changing risks associated
                    with the digital era. Our company was founded on the idea that effective cybersecurity should be
                    proactive, scalable, and easily accessible. We provide customized solutions that go beyond basic
                    defense and instead create resilience.</p>
                <ul>
                    <li><a href="https://www.facebook.com/share/1K5u9spPwj/?mibextid=wwXIfr"><img loading="lazy"
                                src="/static//images/SVGs/facebook.svg" alt="Facebook logo"></a></li>
                    <li><a href="https://www.instagram.com/gridxcyberfirm?igsh=aWJsM3psOHl3enA0"><img loading="lazy"
                                src="/static//images/SVGs/instagram.svg" alt="Instagram logo"></a></li>
                    <li><a href="https://t.me/Gridxcyberfirm"><img loading="lazy" src="/static/images/SVGs/telegram.svg"
                                alt="Telegram logo"></a></li>
                    <li><a href="https://www.youtube.com/@GRIDXCyberFirm"><img loading="lazy" src="/static//images/SVGs//youtube.svg"
                                alt=" Youtube Logo"></a></li>
                    <li><a href="https://www.tiktok.com/@user8827760818518?_t=ZS-8zIS0J1OVlp&_r=1"><img loading="lazy"
                                src="/static//images/SVGs/tiktok.svg" alt="Tiktok Logo"></a></li>
                    <li><a href="https://wa.me/message/UM4UXCT77QFOE1"><img loading="lazy" src="/static//images/SVGs/whatsapp.svg"
                                alt="WhatsApp logo"></a></li>
                </ul>
            </div>
            <div class="links">
                <h3>Useful link</h3>
                <ul>
                    <li><a href="#">Home</a></li>
                    <li><a href="#about">About</a></li>
                    <li><a href="#services">Our Services</a></li>
                    <li><a href="#team">Team</a></li>
                    <li><a href="#contact">Contact us</a></li>
                </ul>
            </div>
            <div class="second">
                <!-- <div >
                    <h3>TRUST</h3>
                    <h3><a href="tel:+123-456-7890">+123-456-7890</a></h3>
                </div> -->
                <div>
                    <h3>info@gridxcyberfirm.com</h3>
                    <form>
                        <label for="email">Your Email Address</label>
                        <input type="email" placeholder="Enter your email here">
                        <button type="submit">Subscribe</button>
                    </form>
                </div>
            </div>
            <h4>© Copyright <strong>Gridxcyberfirm</strong> All rights reserved.</h4>
        </div>
    </footer>
   <script src="/static//scripts/script.js"></script>
   <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script>
        AOS.init();
    </script>
</body>

</html>