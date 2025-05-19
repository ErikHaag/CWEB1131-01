const inputTable = new Map(
    [
        ["firstName", "name"],
        ["lastName", "name"],
        ["email", "email"],
        ["iconChoice", "icon"]
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
        case "icon":
            let newIconInput = document.getElementById("icon");
            if (val == "keep" || val == "default") {
                valid = true;
                newIconInput.removeAttribute("required");
                newIconInput.setAttribute("disabled", "");
            } else if (val == "new") {
                valid = true;
                newIconInput.removeAttribute("disabled");
                newIconInput.setAttribute("required", "");
            }
        default:
            break;
    }
    if (valid) {
        e.target.classList.remove("error");
    } else {
        e.target.classList.add("error");
    }
});