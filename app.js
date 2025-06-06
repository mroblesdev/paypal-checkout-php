const urlPath = "http://localhost/paypal-checkout-php"

const paypalButtons = window.paypal.Buttons({
    style: {
        shape: "rect",
        layout: "vertical",
        color: "gold",
        label: "paypal",
    },
    message: {
        amount: 35000,
    },
    async createOrder() {
        try {
            const response = await fetch(urlPath + "/orders", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                // Usar el parámetro "body" para pasar opcionalmente información adicional del pedido
                body: JSON.stringify({
                    cart: [
                        {
                            id: "1",
                            quantity: 1,
                        },
                    ],
                }),
            });

            const orderData = await response.json();

            if (orderData.id) {
                return orderData.id;
            }
            const errorDetail = orderData?.details?.[0];
            const errorMessage = errorDetail
                ? `${errorDetail.issue} ${errorDetail.description} (${orderData.debug_id})`
                : JSON.stringify(orderData);

            throw new Error(errorMessage);
        } catch (error) {
            console.error(error);
            resultMessage(`No se pudo iniciar el pago con PayPal...<br><br>${error}`);
        }
    },

    onCancel: (data) => {
        alert("Canceló")
    },

});
paypalButtons.render("#paypal-button-container");


// Función de ejemplo para mostrar un resultado al usuario
function resultMessage(message) {
    const container = document.querySelector("#result-message");
    container.innerHTML = message;
}
