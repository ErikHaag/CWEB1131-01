let data = [];
let selectedID = -1;

async function updateTable() {
    let response = await fetch("./API.php");
    try {
        data = await response.json();
    } catch {
        messages.setGoal("Request did not return JSON.\n(Is the database down?)");
        startClock();
        data = {type: "failed", borrows: []};
    }
    // console.log(data);
    let tableHTML = "";
    if (data.type == "error") {
        messages.setGoal("An error occurred:\n" + reply.message);
        startClock();
    }
    if (data?.borrows?.length == 0) {
        tableHTML += "<tr>";
        tableHTML += "<td colspan=\"6\">";
        tableHTML += "<div class=\"center\">";
        tableHTML += "No data";
        tableHTML += "</div>";
        tableHTML += "</td>";
        tableHTML += "</tr>";
    } else {
        for (const row of data.borrows) {
            tableHTML += "<tr>";
            tableHTML += "<td>" + row.userName + "</td>";
            tableHTML += "<td>" + row.itemName + "</td>";
            tableHTML += "<td>" + row.borrowedDate + "</td>";
            tableHTML += "<td>" + row.dueDate + "</td>";
            tableHTML += "<td>" + row.status + "</td>";
            tableHTML += "<td><button id=\"edit" + row.borrowID + "\" class=\"btn btn-warning ms-1\">Edit</button><button id=\"delete" + row.borrowID + "\" class=\"btn btn-danger ms-1\">Delete</button>"
            tableHTML += "</tr>";
        }
    }
    document.getElementById("tableBody").innerHTML = tableHTML;
}

updateTable()

document.getElementById("refresh").addEventListener("click", () => {
    updateTable();
    messages.setGoal("");
    startClock();
});

let messages = new flapDisplay(document.getElementById("messages"));
let clock;

document.addEventListener("DOMContentLoaded", () => {
    let url = new URL(window.location);
    let params = url.searchParams;
    if (params.has("message")) {
        let m = "";
        switch (params.get("message")) {
            case "1":
                m = "Successfully added entry";
                break;
            case "2":
                m = "Successfully changed entry";
                break;
            default:
                break;
        }
        if (m != "") {
            messages.setGoal(m);
            startClock();
        }
    }
});

function startClock() {
    clearInterval(clock);
    //update it regularly
    clock = setInterval(() => {
        let comp = messages.updateCurrent();
        if (comp) {
            //stop when finished
            clearInterval(clock);
        }
    }, 100);
}

async function deleteRow() {
    let response = await fetch("./API.php", {
        method: "POST",
        body: JSON.stringify({
            "requestType": "deleteBorrow",
            "id": selectedID
        }),
        headers: {
            "Content-Type": "application/json"
        }
    });
    let reply;
    try {
        reply = await response.json();
    } catch {
        startClock();
        return;
    }
    if (reply.type == "error") {
        messages.setGoal("An error occurred:\n" + reply.message);
    } else {
        messages.setGoal("Successfully deleted row");
        updateTable();
    }
    startClock();
    deleteRowDialog.close();
}

const deleteRowDialog = document.getElementById("deleteRow");

document.addEventListener("click", (e) => {
    let el = document.elementFromPoint(e.clientX, e.clientY);
    if (el.id.startsWith("delete")) {
        selectedID = Number(el.id.substring(6));
        let row = data.borrows.find((r) => r.borrowID == selectedID);
        deleteRowDialog.children[1].innerText = "User: " + row.userName + ", Item: " + row.itemName + ", Date borrowed: " + row.borrowedDate + ", Date due: " + row.dueDate + ", Status: " + row.status;
        deleteRowDialog.showModal();
        return;
    }
    if (el.id.startsWith("edit")) {
        window.location = "edit.php?id=" + Number(el.id.substring(4));
    }
    switch (el.id) {
        case "closeDeleteRow":
            deleteRowDialog.close();
            break;
        case "acceptDeleteRow":
            deleteRow();
        default:
            break;
    }
});