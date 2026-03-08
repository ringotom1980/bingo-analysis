/*
Path: assets/js/bingo.js
說明：賓果賓果分析頁控制
*/

(function(){

"use strict";

function $(id){
return document.getElementById(id);
}

function pad(n){
return String(n).padStart(2,"0");
}

function ball(n){
return `<span class="ball">${pad(n)}</span>`;
}

async function fetchApi(url){

const res = await fetch(url,{
credentials:"same-origin"
});

return await res.json();

}

/* 最新一期 */

async function loadLatest(){

const json = await fetchApi("api/bingo_latest.php");
const data = json.data;

$("latestIssue").textContent = data.issue_no;
$("latestTime").textContent = data.draw_time;

$("latestBalls").innerHTML =
data.numbers.map(ball).join("");

}

/* 分析 */

async function loadAnalysis(){

const json = await fetchApi("api/bingo_analysis.php");
const data = json.data;

renderRank("hotList",data.hot_top5,"hit_count");
renderRank("coldList",data.cold_top5,"hit_count");
renderRank("missList",data.miss_top5,"last_hit");

}

function renderRank(id,list,key){

if(!list || list.length===0){
$(id).innerHTML="無資料";
return;
}

$(id).innerHTML = list.map(r=>`

<div class="rank-row">

<span class="ball ball-hot">${pad(r.number)}</span>

<span class="rank-value">
${r[key]}
</span>

</div>

`).join("");

}

/* 歷史 */

async function loadHistory(limit=10){

const json = await fetchApi(`api/bingo_history.php?limit=${limit}`);
const data = json.data.list;

$("historyList").innerHTML =
data.map(row=>`

<div class="history-row">

<div class="history-head">

<span>第 ${row.draw_term} 期</span>

<span class="typ-small">
${row.draw_time}
</span>

</div>

<div class="balls-wrap">

${row.numbers.map(ball).join("")}

</div>

</div>

`).join("");

}

/* 指定號碼 */

async function searchNumber(){

const n = parseInt($("numberInput").value);

if(!n || n<1 || n>80){
alert("請輸入1~80");
return;
}

const json = await fetchApi("api/bingo_history.php?limit=100");
const list = json.data.list;

let hit=0;

list.forEach(r=>{
if(r.numbers.includes(n)){
hit++;
}
});

$("numberResult").innerHTML = `
<div class="analysis-result">

<div class="ball ball-hot">${pad(n)}</div>

<div>
最近100期出現
<strong>${hit}</strong> 次
</div>

</div>
`;

}

/* range */

function bindRange(){

document.querySelectorAll(".filter-btn")
.forEach(btn=>{

btn.addEventListener("click",()=>{

const r = btn.dataset.range;

loadHistory(r);

});

});

}

/* init */

async function init(){

await loadLatest();

await loadAnalysis();

await loadHistory(10);

bindRange();

$("btnSearch").addEventListener("click",searchNumber);

}

document.addEventListener("DOMContentLoaded",init);

})();