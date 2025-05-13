const alertDiv = document.getElementById("alerts");
const currentUserTable = document.getElementById("selfTable");
const table = document.getElementById("table");

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

updateUser();

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
            getUserRequestBody.page = Math.min(getUserRequestBody.page, data.lastPage);
            if (data.rows.length == 0) {
                table.innerHTML = "<tr><td colspan=\"3\">No data :(</td></tr>"
            } else {
                let tableHTML = "";
                for (let r of data.rows) {
                    tableHTML += `<tr>
                        <td><img class="thumbnail" src="${r.icon}"></td>
                        <td>${r.username}</td>
                        <td><ul><li>Name: ${r.name}</li><li>Email: ${r.email}</li></ul></td>
                    </tr>`
                }
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

requestAnimationFrame(loadingAnimation);