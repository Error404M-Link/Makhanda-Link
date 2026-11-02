// ===========================
// M-LINK Dashboard JavaScript
// ===========================

// Highlight the active sidebar item
const menuItems = document.querySelectorAll(".menu li");

menuItems.forEach(item => {

    item.addEventListener("click", () => {

        menuItems.forEach(menu => {
            menu.classList.remove("active");
        });

        item.classList.add("active");

    });

});

// Notification icon
const notificationIcon = document.querySelector(".fa-bell");

notificationIcon.addEventListener("click", () => {

    alert("You have 3 new notifications.");

});

// Message icon
const messageIcon = document.querySelector(".fa-envelope");

messageIcon.addEventListener("click", () => {

    alert("You have 2 unread messages.");

});

// Logout confirmation
const logoutButton = document.querySelector(".fa-right-from-bracket");

logoutButton.parentElement.addEventListener("click", () => {

    const confirmLogout = confirm("Are you sure you want to logout?");

    if(confirmLogout){

        alert("You have successfully logged out.");

        // Redirect to login page
        // window.location.href = "login.html";

    }

});

// Welcome message
window.onload = function(){

    const hour = new Date().getHours();

    let greeting = "";

    if(hour < 12){

        greeting = "Good Morning";

    }
    else if(hour < 18){

        greeting = "Good Afternoon";

    }
    else{

        greeting = "Good Evening";

    }

    const heading = document.querySelector(".welcome h1");

    heading.innerHTML = `${greeting}, Faith Mlambo!`;

};