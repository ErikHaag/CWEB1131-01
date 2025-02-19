async function updateTable() {
    let response = await fetch("./API.php");
    let data = await response.json();
    let tableHTML = "";
    if (data.length == 0) {
        tableHTML += "<tr>";
        tableHTML += "<td colspan=\"5\">";
        tableHTML += "<div class=\"center\">";
        tableHTML += "No data";
        tableHTML += "</div>";
        tableHTML += "</td>";
        tableHTML += "</tr>";
    } else {
        for (const row of data) {
            tableHTML += "<tr>";
            tableHTML += "<td>" + row.userName + "</td>";
            tableHTML += "<td>" + row.itemName + "</td>";
            tableHTML += "<td>" + row.borrowedDate + "</td>";
            tableHTML += "<td>" + row.dueDate + "</td>";
            tableHTML += "<td>" + row.status + "</td>";
            tableHTML += "</tr>";
        }
    }
    document.getElementById("tableBody").innerHTML = tableHTML;
}

updateTable()