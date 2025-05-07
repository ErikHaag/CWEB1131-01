document.addEventListener("click", (e) => {
    // get the clicked element
    const element = document.elementFromPoint(e.clientX, e.clientY);
    if (element.id == "loginNav") {
        loginOpen = true;
        updateNav();
    } else if (element.id == "registerNav") {
        loginOpen = false;
        updateNav();
    }
});

function updateNav() {
    let loginNavButton = document.getElementById("loginNav");
    let loginContainer = document.getElementById("loginContainer");
    let registerNavButton = document.getElementById("registerNav");
    let registerContainer = document.getElementById("registerContainer");
    if (loginOpen) {
        loginNavButton.classList.add("disabled");
        registerNavButton.classList.remove("disabled");
    } else {
        loginNavButton.classList.remove("disabled");
        registerNavButton.classList.add("disabled");
    }
    loginContainer.hidden = !loginOpen;
    registerContainer.hidden = loginOpen;
}

updateNav();