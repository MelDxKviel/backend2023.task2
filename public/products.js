window.onload = updateCart;

function updateCart() {
  decodedCookie = decodeURIComponent(document.cookie)
  cookiesData = decodedCookie.split('; ');
  const objects = {};

  for (let i in cookiesData) {
    const cur = cookiesData[i].split('=');
    objects[cur[0]] = cur[1];
  }

  count = 0;
  cart = JSON.parse(objects["cart"]);
  for (const key in cart) {
    count += cart[key]["quantity"];
  }

  const cartCountEl = document.querySelector('#cart-count');
  cartCountEl.textContent = count;
  console.log(count);
}

function addToCart(button) {
  const form = button.parentElement;
  const formData = new FormData(form);
  fetch('/add-cart', { method: 'POST', body: formData }).then(
    response => { updateCart(); }
  ).catch(
    error => {
      console.error('Error adding item to cart', error);
    });
}
