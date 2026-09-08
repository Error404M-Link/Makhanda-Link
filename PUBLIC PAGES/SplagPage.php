
<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>M-Link | Discover Makhanda</title>


    <style>

        /* ==================================================
           RESET
        ================================================== */

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }


        /* ==================================================
           BODY
        ================================================== */

        body {
            font-family: Arial, Helvetica, sans-serif;

            min-height: 100vh;

            background:
                linear-gradient(
                    135deg,
                    #f4f6f8,
                    #e9edf1
                );

            color: #222;

            display: flex;

            justify-content: center;

            align-items: center;

            padding: 40px 20px;

            overflow-x: hidden;
        }


        /* ==================================================
           MAIN CONTAINER
        ================================================== */

        .intro-container {
            width: 100%;

            max-width: 950px;

            text-align: center;
        }


        /* ==================================================
           BRAND
        ================================================== */

        .brand {
            font-size: 32px;

            font-weight: 700;

            letter-spacing: 2px;

            margin-bottom: 8px;

            color: #222;
        }


        .brand-subtitle {
            font-size: 15px;

            color: #666;

            margin-bottom: 25px;
        }


        /* ==================================================
           CARD
        ================================================== */

        .intro-card {
            width: 100%;

            background: #fff;

            border-radius: 20px;

            padding: 25px;

            box-shadow:
                0 10px 35px rgba(0, 0, 0, 0.12);

            overflow: hidden;
        }


        /* ==================================================
           SLIDESHOW
        ================================================== */

        .slideshow {
            position: relative;

            width: 100%;

            height: 430px;

            overflow: hidden;

            border-radius: 15px;

            background: #eee;
        }


        /* ==================================================
           SLIDES
        ================================================== */

        .slide {
            position: absolute;

            inset: 0;

            width: 100%;

            height: 100%;

            opacity: 0;

            visibility: hidden;

            transition:
                opacity 0.8s ease,
                visibility 0.8s ease;
        }


        .slide.active {
            opacity: 1;

            visibility: visible;
        }


        /* ==================================================
           SLIDE IMAGE
        ================================================== */

        .slide img {
            width: 100%;

            height: 100%;

            object-fit: cover;

            display: block;

            transform: scale(1.04);

            transition:
                transform 3.5s ease;
        }


        .slide.active img {
            transform: scale(1);
        }


        /* ==================================================
           IMAGE OVERLAY
        ================================================== */

        .slide::after {
            content: "";

            position: absolute;

            inset: 0;

            background:
                linear-gradient(
                    to top,
                    rgba(0, 0, 0, 0.7),
                    rgba(0, 0, 0, 0.05)
                );
        }


        /* ==================================================
           SLIDE TEXT
        ================================================== */

        .slide-content {
            position: absolute;

            left: 30px;

            right: 30px;

            bottom: 25px;

            z-index: 2;

            color: #fff;

            text-align: left;
        }


        .slide-content h2 {
            font-size: 30px;

            margin-bottom: 8px;

            text-shadow:
                0 2px 5px rgba(0, 0, 0, 0.5);
        }


        .slide-content p {
            font-size: 15px;

            line-height: 1.5;

            max-width: 650px;

            text-shadow:
                0 1px 4px rgba(0, 0, 0, 0.6);
        }


        /* ==================================================
           ARROWS
        ================================================== */

        .arrow {
            position: absolute;

            top: 50%;

            transform: translateY(-50%);

            z-index: 5;

            width: 42px;

            height: 42px;

            border: none;

            border-radius: 50%;

            background:
                rgba(255, 255, 255, 0.9);

            color: #222;

            font-size: 20px;

            cursor: pointer;

            display: flex;

            align-items: center;

            justify-content: center;

            box-shadow:
                0 3px 10px rgba(0, 0, 0, 0.15);

            transition:
                0.25s ease;
        }


        .arrow:hover {
            background: #fff;

            transform:
                translateY(-50%) scale(1.08);
        }


        .prev {
            left: 15px;
        }


        .next {
            right: 15px;
        }


        /* ==================================================
           DOTS
        ================================================== */

        .dots {
            display: flex;

            justify-content: center;

            align-items: center;

            gap: 8px;

            margin-top: 20px;
        }


        .dot {
            width: 9px;

            height: 9px;

            padding: 0;

            border: none;

            border-radius: 50%;

            background: #c7c7c7;

            cursor: pointer;

            transition:
                0.3s ease;
        }


        .dot:hover {
            transform: scale(1.2);
        }


        .dot.active {
            width: 25px;

            border-radius: 10px;

            background: #222;
        }


        /* ==================================================
           INTRO TEXT
        ================================================== */

        .intro-text {
            margin-top: 22px;

            margin-bottom: 20px;
        }


        .intro-text h1 {
            font-size: 25px;

            margin-bottom: 8px;

            color: #222;
        }


        .intro-text p {
            font-size: 14px;

            color: #666;

            line-height: 1.5;
        }


        /* ==================================================
           CONTINUE BUTTON
        ================================================== */

        .continue-btn {
            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 10px;

            padding: 13px 30px;

            min-width: 210px;

            border-radius: 30px;

            background: #222;

            color: #fff;

            text-decoration: none;

            font-size: 15px;

            font-weight: 600;

            transition:
                0.3s ease;

            box-shadow:
                0 5px 15px rgba(0, 0, 0, 0.15);
        }


        .continue-btn:hover {
            background: #444;

            transform: translateY(-2px);

            box-shadow:
                0 7px 20px rgba(0, 0, 0, 0.2);
        }


        .continue-arrow {
            font-size: 18px;

            transition:
                transform 0.3s ease;
        }


        .continue-btn:hover .continue-arrow {
            transform: translateX(5px);
        }


        /* ==================================================
           PROGRESS BAR
        ================================================== */

        .progress-container {
            width: 100%;

            height: 3px;

            margin-top: 22px;

            background: #eee;

            border-radius: 5px;

            overflow: hidden;
        }


        .progress-bar {
            width: 0%;

            height: 100%;

            background: #222;
        }


        /* ==================================================
           FOOTER
        ================================================== */

        .footer-text {
            margin-top: 18px;

            font-size: 12px;

            color: #888;
        }


        /* ==================================================
           TABLET
        ================================================== */

        @media (max-width: 768px) {

            body {
                padding: 25px 15px;
            }


            .brand {
                font-size: 28px;
            }


            .intro-card {
                padding: 18px;

                border-radius: 16px;
            }


            .slideshow {
                height: 360px;

                border-radius: 12px;
            }


            .slide-content {
                left: 20px;

                right: 20px;

                bottom: 20px;
            }


            .slide-content h2 {
                font-size: 25px;
            }

        }


        /* ==================================================
           MOBILE
        ================================================== */

        @media (max-width: 480px) {

            body {
                padding: 20px 12px;
            }


            .brand {
                font-size: 25px;
            }


            .brand-subtitle {
                font-size: 13px;

                margin-bottom: 18px;
            }


            .intro-card {
                padding: 12px;

                border-radius: 14px;
            }


            .slideshow {
                height: 300px;

                border-radius: 10px;
            }


            .slide-content {
                left: 16px;

                right: 16px;

                bottom: 16px;
            }


            .slide-content h2 {
                font-size: 21px;
            }


            .slide-content p {
                font-size: 13px;
            }


            .arrow {
                width: 36px;

                height: 36px;

                font-size: 17px;
            }


            .prev {
                left: 10px;
            }


            .next {
                right: 10px;
            }


            .intro-text h1 {
                font-size: 21px;
            }


            .intro-text p {
                font-size: 13px;
            }


            .continue-btn {
                width: 100%;

                min-width: 0;
            }

        }

    </style>

</head>


<body>


    <!-- ==================================================
         INTRO CONTAINER
    ================================================== -->

    <main class="intro-container">


        <!-- BRAND -->

        <div class="brand">
            M-LINK
        </div>


        <div class="brand-subtitle">
            Discover Makhanda
        </div>


        <!-- ==================================================
             CARD
        ================================================== -->

        <section class="intro-card">


            <!-- ==================================================
                 SLIDESHOW
            ================================================== -->

            <div class="slideshow">


                <!-- SLIDE 1 -->

                <div class="slide active">

                    <img src="images/makhanda.jpg"
                         alt="Makhanda">

                    <div class="slide-content">

                        <h2>
                            Welcome to Makhanda
                        </h2>

                        <p>
                            Discover the history, culture and
                            beauty of our community.
                        </p>

                    </div>

                </div>


                <!-- SLIDE 2 -->

                <div class="slide">

                    <img src="images/1820-settlers.jpg"
                         alt="1820 Settlers Monument">

                    <div class="slide-content">

                        <h2>
                            1820 Settlers Monument
                        </h2>

                        <p>
                            Explore one of Makhanda's most
                            recognisable landmarks.
                        </p>

                    </div>

                </div>


                <!-- SLIDE 3 -->

                <div class="slide">

                    <img src="images/cathedral.jpg"
                         alt="Historic Makhanda">

                    <div class="slide-content">

                        <h2>
                            Historic Makhanda
                        </h2>

                        <p>
                            Experience the historic architecture
                            and heritage of our city.
                        </p>

                    </div>

                </div>


                <!-- SLIDE 4 -->

                <div class="slide">

                    <img src="images/national-arts-festival.jpg"
                         alt="National Arts Festival">

                    <div class="slide-content">

                        <h2>
                            National Arts Festival
                        </h2>

                        <p>
                            Celebrate creativity, art and culture
                            in the heart of Makhanda.
                        </p>

                    </div>

                </div>


                <!-- SLIDE 5 -->

                <div class="slide">

                    <img src="images/makhanda-landscape.jpg"
                         alt="Makhanda landscape">

                    <div class="slide-content">

                        <h2>
                            Explore Makhanda
                        </h2>

                        <p>
                            There is always something new to
                            discover in our community.
                        </p>

                    </div>

                </div>


                <!-- ==================================================
                     PREVIOUS
                ================================================== -->

                <button
                    type="button"
                    class="arrow prev"
                    id="prevBtn"
                    aria-label="Previous attraction">

                    &#10094;

                </button>


                <!-- ==================================================
                     NEXT
                ================================================== -->

                <button
                    type="button"
                    class="arrow next"
                    id="nextBtn"
                    aria-label="Next attraction">

                    &#10095;

                </button>


            </div>


            <!-- ==================================================
                 DOTS
            ================================================== -->

            <div class="dots"
                 id="dots">

                <button
                    type="button"
                    class="dot active"
                    aria-label="Slide 1">
                </button>

                <button
                    type="button"
                    class="dot"
                    aria-label="Slide 2">
                </button>

                <button
                    type="button"
                    class="dot"
                    aria-label="Slide 3">
                </button>

                <button
                    type="button"
                    class="dot"
                    aria-label="Slide 4">
                </button>

                <button
                    type="button"
                    class="dot"
                    aria-label="Slide 5">
                </button>

            </div>


            <!-- ==================================================
                 INTRODUCTION
            ================================================== -->

            <div class="intro-text">

                <h1>
                    Welcome to M-Link
                </h1>

                <p>
                    Connecting the Makhanda community,
                    one neighbourhood at a time.
                </p>

            </div>


            <!-- ==================================================
                 CONTINUE
            ================================================== -->

            <a href="index.html"
               class="continue-btn">

                Continue to M-Link

                <span class="continue-arrow">
                    &#8594;
                </span>

            </a>


            <!-- ==================================================
                 PROGRESS BAR
            ================================================== -->

            <div class="progress-container">

                <div class="progress-bar"
                     id="progressBar">
                </div>

            </div>


        </section>


        <div class="footer-text">
            Discover • Connect • Communicate
        </div>


    </main>



    <!-- ==================================================
         JAVASCRIPT
    ================================================== -->

    <script>

        /* ==================================================
           SETTINGS
        ================================================== */

        const slideDuration = 3000;


        /* ==================================================
           ELEMENTS
        ================================================== */

        const slides =
            document.querySelectorAll(".slide");

        const dots =
            document.querySelectorAll(".dot");

        const prevBtn =
            document.getElementById("prevBtn");

        const nextBtn =
            document.getElementById("nextBtn");

        const progressBar =
            document.getElementById("progressBar");


        /* ==================================================
           VARIABLES
        ================================================== */

        let currentSlide = 0;

        let slideTimer;

        let progressTimer;


        /* ==================================================
           SHOW SLIDE
        ================================================== */

        function showSlide(index) {

            slides.forEach(function(slide) {

                slide.classList.remove("active");

            });


            dots.forEach(function(dot) {

                dot.classList.remove("active");

            });


            slides[index].classList.add("active");

            dots[index].classList.add("active");

            currentSlide = index;

            startProgress();

        }


        /* ==================================================
           NEXT SLIDE
        ================================================== */

        function nextSlide() {

            let nextIndex =
                currentSlide + 1;


            if (nextIndex >= slides.length) {

                nextIndex = 0;

            }


            showSlide(nextIndex);

            restartTimer();

        }


        /* ==================================================
           PREVIOUS SLIDE
        ================================================== */

        function previousSlide() {

            let previousIndex =
                currentSlide - 1;


            if (previousIndex < 0) {

                previousIndex =
                    slides.length - 1;

            }


            showSlide(previousIndex);

            restartTimer();

        }


        /* ==================================================
           START TIMER
        ================================================== */

        function startTimer() {

            slideTimer =
                setInterval(
                    nextSlide,
                    slideDuration
                );

        }


        /* ==================================================
           RESTART TIMER
        ================================================== */

        function restartTimer() {

            clearInterval(slideTimer);

            startTimer();

        }


        /* ==================================================
           PROGRESS BAR
        ================================================== */

        function startProgress() {

            clearInterval(progressTimer);

            progressBar.style.width = "0%";


            const startTime =
                Date.now();


            progressTimer =
                setInterval(function() {

                    const elapsed =
                        Date.now() - startTime;


                    const percentage =
                        Math.min(
                            (elapsed / slideDuration) * 100,
                            100
                        );


                    progressBar.style.width =
                        percentage + "%";


                }, 50);

        }


        /* ==================================================
           NEXT BUTTON
        ================================================== */

        nextBtn.addEventListener(
            "click",
            nextSlide
        );


        /* ==================================================
           PREVIOUS BUTTON
        ================================================== */

        prevBtn.addEventListener(
            "click",
            previousSlide
        );


        /* ==================================================
           DOT BUTTONS
        ================================================== */

        dots.forEach(function(dot, index) {

            dot.addEventListener(
                "click",
                function() {

                    showSlide(index);

                    restartTimer();

                }
            );

        });


        /* ==================================================
           START
        ================================================== */

        showSlide(0);

        startTimer();

    </script>


</body>
</html>

