const imageUpload = document.getElementById("imageUpload"); 
const profileImage = document.getElementById("profileImage");
const deleteButton = document.querySelector(".delete-btn"); 

imageUpload.addEventListener("change", function(){
    const file = this.files[0];
    if (file){
        const reader = new FileReader(); 
        reader.onload = function (e){
            profileImage.src = e.target.result; 
        };
        reader.readAsDataURL(file);
    }  
}); 

deleteButton.addEventListener("click",function(){
    profileImage.src = "images/default-profile.png";
    imageUpload.value = "";
}); 

