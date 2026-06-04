// Tabs
// document.querySelectorAll(".tab").forEach(tab => {
//   tab.addEventListener("click", () => {
//     document.querySelectorAll(".tab, .tab-content")
//       .forEach(el => el.classList.remove("active"));

//     tab.classList.add("active");
//     document.getElementById(tab.dataset.tab).classList.add("active");
//   });
// });

// Coins


// Copy wallet
document.querySelector(".copy-btn").addEventListener("click", () => {
  copyEle('wallets', 'Wallet address copied!')
});

document.querySelector(".copy-amm-btn").addEventListener("click", () => {
  copyEle('amount', 'Amount  copied!')
});

let copyEle = (id, message)=>{
  const wallet = document.getElementById(id).value;
  navigator.clipboard.writeText(wallet);
  alert(message);

}

// Countdown timer
let time = 15 * 60;
setInterval(() => {
  if (time <= 0) return;
  time--;
  const m = String(Math.floor(time / 60)).padStart(2, "0");
  const s = String(time % 60).padStart(2, "0");
  document.getElementById("timer").textContent = `${m}:${s}`;
}, 1000);