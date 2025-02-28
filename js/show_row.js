const urlParams = new URLSearchParams(window.location.search);

let rowId = parseInt(urlParams.get('rowId') || 1) + 1;
let approvalId = parseInt(urlParams.get('approvalId')) || 0;


const columnMap = {
    1: "K", // Approval1
    2: "M", // Approval2
    3: "O"  // Approval3
};


let landingPage = document.getElementById("landing-page");


fetch(`get_cell.php?rowId=${rowId}&approvalId=${approvalId}`)
    .then(response => response.json())
    .then(({ approval_status }) =>
        (["Yes", "No"].includes(approval_status?.trim()) && displayMessage(approval_status === "Yes")) ||
        fetch(`get_row.php?rowId=${rowId}&approvalId=${approvalId}`)
            .then(response => response.json())
            .then(displayData)
    )
    .catch(error => console.error("Error fetching data:", error));



function displayData(data) {

    let detailsContainer = document.getElementById("details-request");

    let dataContainer = document.getElementById("data-container") || {};
    dataContainer.innerHTML = "";

    let headerDiv = document.createElement("div");
    headerDiv.id = "header";

    let header = document.createElement("h2");
    header.innerText = "APPROVAL REQUEST";

    headerDiv.appendChild(header);
    dataContainer.prepend(headerDiv);

    delete data["Processed"];


    let approverValKeys = Object.keys(data).filter((key) => {
        return key.includes("Approval")
    })

    let approvalStatusMap = approverValKeys.map(key => {
        let approverVal = data[key] ? data[key].toLowerCase() : "";
        return approverVal === "yes" ? " has Approved" :
               approverVal === "no" ? " didn't Approve" :
               " yet to Approve";
    });

    Object.keys(data).filter((key) => {
        return key.includes("Email address of")
    }).forEach((key,index) => {
        // approverVal = data[approverValKeys[index]];
        approverVal = approvalStatusMap[index];
        data[key]+=approverVal;

        delete data[approverValKeys[index]];
    })

    Object.keys(data).forEach((item) => {
        let row = document.createElement("div");
        row.classList.add("data-row");

        let label = document.createElement("div");
        label.classList.add("label");
        label.innerText = item.trim().endsWith(":") ? item.trim() : item.trim() + ":";

        let value = document.createElement("div");
        value.classList.add("value");
        value.innerText = data[item];

        row.appendChild(label);
        row.appendChild(value);
        dataContainer.appendChild(row);
    })

    detailsContainer.appendChild(dataContainer);
    landingPage.append(detailsContainer);

    // let lastRow = dataContainer.querySelector(".data-row:last-child");
    // if (lastRow) {
    //     lastRow.style.display = "none";
    // }


    setupApprovalButtons();
}

function setupApprovalButtons() {
    let approveDiv = document.getElementById("approval-section");
    approveDiv.innerHTML = `<br><strong>Do you want to approve?</strong><br>`;

    ["Yes", "No"].forEach(text => {
        let button = document.createElement("button");
        button.innerText = text;
        let value = (text == "Yes");
        button.classList.add(text.toLowerCase());
        button.onclick = () => handleDecision(value);
        approveDiv.appendChild(button);
    });

    landingPage.appendChild(approveDiv);
}

function handleDecision(isApproved) {
    fetch(`update_yes_no.php?rowId=${rowId}&column=${columnMap[approvalId]}&isApproved=${isApproved}`)
        .then(() => displayMessage(isApproved));
}


function displayMessage(isApproved) {
    document.body.innerHTML = "";

    let messageContainer = document.createElement("div");
    messageContainer.classList.add("message-container");

    let message = document.createElement("div");
    message.classList.add("message");
    message.innerText = isApproved ? "Your approval has been submitted ✅" : "Your rejection has been submitted ❌";

    messageContainer.appendChild(message);
    document.body.appendChild(messageContainer);
}
