function calculateTime() {

    let vehicleType =
        document.getElementById("vehicleType").value;

    let vehiclesAhead =
        parseInt(document.getElementById("vehiclesAhead").value);

    let activePumps =
        parseInt(document.getElementById("activePumps").value);

    if (
        isNaN(vehiclesAhead) ||
        isNaN(activePumps) ||
        activePumps <= 0
    ) {
        alert("Please enter valid values.");
        return;
    }

    let serviceTime = 0;

    if (vehicleType === "Bike") {
        serviceTime = 2;
    }
    else if (vehicleType === "Car") {
        serviceTime = 5;
    }
    else if (vehicleType === "Truck") {
        serviceTime = 8;
    }

    let estimatedTime =
        (vehiclesAhead * serviceTime) / activePumps;

    document.getElementById("result").innerHTML =
        "Estimated Waiting Time: " +
        Math.ceil(estimatedTime) +
        " Minutes";
}