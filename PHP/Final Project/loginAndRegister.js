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

const inputTable = new Map(
    [
        ["usernameL", "username"],
        ["passwordL", "password"],
        ["firstNameR", "name"],
        ["lastNameR", "name"],
        ["usernameR", "username"],
        ["passwordR", "password"],
        ["emailR", "email"]
    ]
);

document.addEventListener("change", (e) => {
    if (!inputTable.has(e.target.id)) {
        return;
    }
    let val = e.target.value.trim();
    e.target.value = val;
    let inputType = inputTable.get(e.target.id);
    let valid = false;
    switch (inputType) {
        case "email":
            if (val.length > 50) break;
            if (!/^[\w.]+@[\w.]+\.[A-Za-z0-9]+$/.test(val)) break;
            valid = true;
            break;
        case "name":
            if (val.length > 50) break;
            if (!/^[A-Z][a-z]+$/.test(val)) break;
            valid = true;
            break;
        case "password":
            if (val.length > 64) break;
            if (val.length < 8) break;
            if (!/^[!-~]{8,64}$/.test(val)) break;
            valid = true;
            break;
        case "username":
            if (val.length > 30) break;
            if (val.length < 5) break;
            if (!/^[\w\-]{5,30}$/.test(val)) break;
            valid = true;
            break;
        default:
            break;
    }
    if (valid) {
        e.target.classList.remove("error");
    } else {
        e.target.classList.add("error");
    }
})