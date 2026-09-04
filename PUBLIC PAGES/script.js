document.getElementById("contactForm").addEventListener("submit", function(e)) {
    e.preventDefault();

    const name = document.getElementById("name").value.trim();
    const email = document.getElementById("email").value.trim();
    const message = document.getElementById("message").value.trim();

    if(!name || !email || !message){
        alert("Please fill in all fields.");
        return;
    }

    const emailPattern = ;
    if (!email.match(emailPattern)){
        alert("Please enter a valid email address.");
        return;
    }

    alert("Thank you for contacting us, " + name + "!");
    document.getElementById("contactForm").reset();

}; 