const nameHeader = document.getElementById("name");
const emailHeader = document.getElementById("email");
const addressHeader = document.getElementById("address");
const table = document.getElementById("table");
const pageNumber = document.getElementById("pageNumber");
const search = document.getElementById("search");
const rowsSelect = document.getElementById("rows");

let rows = [];

let sort = {
    requestType: "getData",
    column: "id",
    direction: "asc",
    page: 0,
    rows: 5,
    search: ""
};

async function deleteCustomer(id) {
    const choice = await swal.fire({
        title: "Are you sure?",
        text: "You won't be able to recover this customer!",
        icon: "warning",
        showCancelButton: true,
        cancelButtonColor: "#3085d6",
        confirmButtonColor: "#d33",
        confirmButtonText: "Yes, delete it"
    });
    if (!choice.isConfirmed) {
        return;
    } 
    const response = await fetch("./API.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({
            requestType: "deleteCustomer",
            id: id
        })
    });
    let reply;
    try {
        reply = await response.json();
    } catch (e) {
        await swal.fire({
            title: "Oops",
            text: "An error occurred.",
            icon: "error"
        });
        return;
    }
    if (reply.type == "success") {
        await swal.fire({
            title: "Success!",
            text: "Successfully removed customer.",
            icon: "success"
        });
        updateTable();
    } else if (reply.type == "error") {
        await swal.fire({
            title: "Oops",
            text: "An error occurred.",
            icon: "error"
        });
    }
}

function updateHeaders() {
    if (sort.column == "name") {
        nameHeader.className = sort.direction == "asc" ? "sortUp" : "sortDown";
    } else {
        nameHeader.className = "sortable";
    }
    if (sort.column == "email") {
        emailHeader.className = sort.direction == "asc" ? "sortUp" : "sortDown";
    } else {
        emailHeader.className = "sortable";
    }
    if (sort.column == "address") {
        addressHeader.className = sort.direction == "asc" ? "sortUp" : "sortDown";
    } else {
        addressHeader.className = "sortable";
    }
}

function updateSort(col) {
    if (col == sort.column) {
        if (sort.direction == "asc") {
            sort.direction = "desc";
            return;
        }
        sort.column = "id";
        sort.direction = "asc";
        return;
    }
    sort.column = col;
    sort.direction = "asc";
    return;
}

async function updateTable() {
    const response = await fetch("./API.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify(sort)
    });
    let reply;
    try {
        reply = await response.json();
    } catch {
        console.error("Server didn't responsed with JSON (Is the database down?)");
        return;
    }
    if (reply.type == "success") {
        if (reply.data.length == 0) {
            table.innerHTML = "<td colspan=\"6\">No data :(</td>";
            return;
        }
        rows = reply.data;
        let tableHTML = ""
        for (const row of reply.data) {
            tableHTML += "<tr>";
            for (const col of ["customerID", "storeID", "name", "email", "address"]) {
                tableHTML += "<td>";
                tableHTML += row[col];
                tableHTML += "</td>";
            }
            tableHTML += "<td>";
            // tableHTML += "<button id=\"read" + row.customerID + "\" class=\"read\">Read</button>";
            tableHTML += "<a href=\"edit.php?id=" + row.customerID + "\" class=\"btn edit\">Edit</a>";
            tableHTML += "<button id=\"delete" + row.customerID + "\" class=\"btn delete\">Delete</button>";
            tableHTML += "</td>"
            tableHTML += "</tr>";
        }
        table.innerHTML = tableHTML;
    }
}

document.addEventListener("click", (e) => {
    let el = document.elementFromPoint(e.x, e.y);
    let buttonId = el?.id ?? "";
    switch (buttonId) {
        case "name":
        case "email":
        case "address":
            updateSort(buttonId);
            updateHeaders();
            updateTable();
            break;
        case "previous":
            if (sort.page > 0) {
                sort.page--;
            } else {
                sort.page = 0;
            }
            pageNumber.innerText = sort.page + 1;
            updateTable();
            break;
        case "next":
            pageNumber.innerText = (++sort.page) + 1;
            updateTable();
            break;
        default:
            break;
    }
    if (buttonId.startsWith("delete")) {
        deleteCustomer(Number(buttonId.substring(6)));
    }
});

search.addEventListener("input", () => {
    sort.search = search.value;
    sort.page = 0;
    pageNumber.innerText = "1";
    updateTable();
});

rowsSelect.addEventListener("change", () => {
    sort.rows = Number(rowsSelect.value);
    sort.page = 0;
    pageNumber.innerText = "1";
    updateTable();
})

updateTable();