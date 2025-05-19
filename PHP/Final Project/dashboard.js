const alertDiv = document.getElementById("alerts");
const currentUserTable = document.getElementById("selfTable");
const table = document.getElementById("table");
const pageDisplay = document.getElementById("pageNumber");


const getUserRequestBody = {
    username: username,
    password: password,
    getSelf: false,
    sortColumn: "id",
    sortDir: "asc",
    query: "",
    rows: 5,
    page: 0
};

let lastPage = 0;

const updatePageDisp = () => {pageDisplay.innerText = (getUserRequestBody.page + 1) + "/" + (lastPage + 1);};

async function updateUser() {
    let response = await fetch("./api.php", {
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({
            username: username,
            password: password,
            getSelf: true
        }),
        method: "POST"
    });
    let data = await response.json();
    switch (response.status) {
        case 200:
            alertDiv.children[0].innerHTML = "";
            if (alertDiv.children[1].innerHTML == "") {
                alertDiv.hidden = true;
            }
            currentUserTable.innerHTML = `<tr>
                <th>Icon</th>
                <td><img class="thumbnail" src="${data.row.icon}"></td>
            </tr>
            <tr>
                <th>Username</th>
                <td>${data.row.username}</td>
            </tr>
            <tr>
                <th>Name</th>
                <td>${data.row.name}</td>
            </tr>
            <tr>
                <th>Email</th>
                <td>${data.row.email}</td>
            </tr>`;
            break;
        case 503:
            alertDiv.children[0].innerHTML = "<li>" + data.messages.join("</li><li>") + "</li>";
            alertDiv.hidden = false;
            break;
        default:
            alertDiv.children[0].innerHTML = "<li> Unhandled response code " + response.status;
            break;
    }
}

async function updateTable() {
    table.innerHTML = "<td id=\"loadingTd\" colspan=\"3\"></td>";
    requestAnimationFrame(loadingAnimation);
    let response = await fetch("./api.php", {
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify(getUserRequestBody),
        method: "POST"
    });
    let data = await response.json();
    switch (response.status) {
        case 200:
            lastPage = data.lastPage;
            getUserRequestBody.page = Math.min(getUserRequestBody.page, lastPage);
            if (data.rows.length == 0) {
                table.innerHTML = "<tr><td colspan=\"3\">No data :(</td></tr>"
            } else {
                let tableHTML = "";
                for (let r of data.rows) {
                    tableHTML += `<tr>
                        <td><img class="thumbnail" src="${r.icon}"></td>
                        <td>${r.username}</td>
                        <td><ul><li>Name: ${r.name}</li><li>Email: ${r.email}</li></ul>${additionalButtons(r.username)}</td>
                    </tr>`;
                }
                table.innerHTML = tableHTML;
            }
            break;
    }
}

// System critical
function loadingAnimation() {
    let time = Math.floor(document.timeline.currentTime / 125) % 14;
    if (time > 7) {
        time = 14 - time;
    }

    let lTD = document.getElementById("loadingTd");
    if (lTD == null) {
        return;
    }
    let fill = "";
    switch (time) {
        case 0:
            fill = ",.......";
            break;
        case 1:
            fill = ".,......";
            break;
        case 2:
            fill = "..,.....";
            break;
        case 3:
            fill = "...,....";
            break;
        case 4:
            fill = "....,...";
            break;
        case 5:
            fill = ".....,..";
            break;
        case 6:
            fill = "......,.";
            break;
        case 7:
            fill = ".......,";
            break;
        default:
            break;
    }

    lTD.textContent = "Loading" + fill;

    requestAnimationFrame(loadingAnimation);
}

updateUser();
updateTable();


document.addEventListener("click", (e) => {
    switch (e.target.id) {
        case "prevPage":
            if (getUserRequestBody.page >= 1) {
                getUserRequestBody.page--;
                updatePageDisp();
                updateTable();
            }
            break;
        case "nextPage":
            if (getUserRequestBody.page < lastPage) {
                getUserRequestBody.page++;
                updatePageDisp();
                updateTable();
            }
            break;
        default:
            handleAdditionalButtons(e.target.id);
            break; 
    }
});

document.addEventListener("change", (e) => {
    switch (e.target.id) {
        case "rowCount":
            getUserRequestBody.rows = Number(e.target.value);
            getUserRequestBody.page = 0;
            updatePageDisp();
            updateTable();
            break;
        case "query":
            getUserRequestBody.query = e.target.value;
            getUserRequestBody.page = 0;
            updatePageDisp();
            updateTable();
            break;
        case "sortCol":
            getUserRequestBody.sortColumn = e.target.value;
            getUserRequestBody.page = 0;
            updatePageDisp();
            updateTable();
            break;
        case "sortDir":
            getUserRequestBody.sortDir = e.target.value;
            getUserRequestBody.page = 0;
            updatePageDisp();
            updateTable();
            break;
        default:
            break;
    }
});