const menuBtn = document.getElementById("menuBtn");
const sidebar = document.getElementById("sidebar");

menuBtn.addEventListener("click", function () {

    sidebar.classList.toggle("open");

    const icon = menuBtn.querySelector("i");

    if (sidebar.classList.contains("open")) {

        icon.classList.remove("fa-bars");
        icon.classList.add("fa-mark");

    } else {

        icon.classList.remove("fa-xmark");
        icon.classList.add("fa-bars");

    }

});