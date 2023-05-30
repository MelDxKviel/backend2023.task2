const priceElements = document.querySelectorAll('.price');
const quantityElements = document.querySelectorAll('.quantity');
const totalElements = document.querySelectorAll('.total');

let totalQuantity = 0;
let totalPrice = 0;

for (let i = 0; i < priceElements.length; i++) {
  const quantity = parseInt(quantityElements[i].textContent);
  const total = parseFloat(totalElements[i].textContent);
  
  totalQuantity += quantity;
  totalPrice += total;
}

document.getElementById('total-quantity').textContent = totalQuantity;
document.getElementById('total-price').textContent = totalPrice
