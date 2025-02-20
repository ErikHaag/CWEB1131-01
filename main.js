let data = [];
async function updateTable() {
    let response = await fetch("./API.php");
    data = await response.json();
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
            tableHTML += "<td><button id=\"delete" + row.borrowID + "\" class=\"btn btn-danger\">Delete</button>"
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

async function deleteRow(delID) {
    let reponse = await fetch("./API.php", {
        method: "POST",
        body: JSON.stringify({
            "requestType": "deleteBorrow",
            "id": delID
        }),
        headers: {
            "Content-Type": "application/json"
        }
    });
    let reply = await reponse.json();
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
        delID = el.id.substring(6);
        let row = data.borrows.find((r) => r.borrowID == delID);
        deleteRowDialog.children[1].innerText = "User: " + row.userName + ", Item: " + row.itemName + ", Date borrowed: " + row.borrowedDate + ", Date due: " + row.dueDate + ", Status: " + row.status;
        deleteRowDialog.showModal();
        return;
    }
    switch (el.id) {
        case "closeDeleteRow":
            deleteRowDialog.close();
            break;
        case "acceptDeleteRow":
            deleteRow(delID);
        default:
            break;
    }
});