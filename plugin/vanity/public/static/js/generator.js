'use strict';
// ── 链配置 ────────────────────────────────────────────────────────────────────
const COIN_CONFIG = {
    btc:  { label:'Bitcoin (BTC)',  icon:'₿', color:'#f7931a', skip:1, addrTypes:['p2pkh','p2sh','bech32'], addrLabels:['Legacy (1...)','P2SH (3...)','Bech32 (bc1...)'] },
    ltc:  { label:'Litecoin (LTC)', icon:'Ł', color:'#bebebe', skip:1, addrTypes:['p2pkh','p2sh','bech32'], addrLabels:['Legacy (L...)','P2SH (M...)','Bech32 (ltc1...)'] },
    doge: { label:'Dogecoin (DOGE)',icon:'Ð', color:'#ba9f33', skip:1, addrTypes:['p2pkh'], addrLabels:['D...'] },
    xrp:  { label:'Ripple (XRP)',   icon:'✕', color:'#00aae4', skip:1, addrTypes:['default'], addrLabels:['r...'] },
    eth:  { label:'Ethereum (ETH)', icon:'Ξ', color:'#627eea', skip:2, addrTypes:['default'], addrLabels:['0x...'] },
    tron: { label:'Tron (TRX)',     icon:'T', color:'#FF0013', skip:1, addrTypes:['default'], addrLabels:['T...'] },
    sol:  { label:'Solana (SOL)',   icon:'◎', color:'#9945ff', skip:0, addrTypes:['default'], addrLabels:['Base58'] },
    xlm:  { label:'Stellar (XLM)', icon:'*', color:'#08b5e5', skip:1, addrTypes:['default'], addrLabels:['G... (仅限 A-Z, 2-7)'], charset:'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567' },
    ada:  { label:'Cardano (ADA)', icon:'₳', color:'#0033ad', skip:5, addrTypes:['default'], addrLabels:['addr1...'] },
    dot:  { label:'Polkadot (DOT)',icon:'●', color:'#e6007a', skip:1, addrTypes:['default'], addrLabels:['SS58'] },
    atom: { label:'Cosmos (ATOM)', icon:'⚛', color:'#2e3148', skip:7, addrTypes:['default'], addrLabels:['cosmos1...'] },
    apt:  { label:'Aptos (APT)',   icon:'A', color:'#00d4c8', skip:2, addrTypes:['default'], addrLabels:['0x...'] },
};

// ── 状态 ──────────────────────────────────────────────────────────────────────
let workers=[],isGenerating=false,startTime=0,totalAttempts=0,lastAttempts=0,lastUpdateTime=0,foundResult=null,updateInterval=null;
let currentCoin='btc';

// ── 初始化硬币选择器 ─────────────────────────────────────────────────────────
function initCoinTabs(){
    const tabs=document.getElementById('coinTabs');
    Object.entries(COIN_CONFIG).forEach(([coin,cfg])=>{
        const btn=document.createElement('button');
        btn.className='coin-tab'+(coin==='btc'?' active':'');
        btn.dataset.coin=coin;
        btn.style.setProperty('--cc',cfg.color);
        btn.innerHTML=`<span class="coin-icon">${cfg.icon}</span><span class="coin-label">${coin.toUpperCase()}</span>`;
        btn.onclick=()=>selectCoin(coin);
        tabs.appendChild(btn);
    });
}

function selectCoin(coin){
    if(isGenerating)return;
    currentCoin=coin;
    const cfg=COIN_CONFIG[coin];
    // 更新 tab 高亮
    document.querySelectorAll('.coin-tab').forEach(b=>b.classList.toggle('active',b.dataset.coin===coin));
    // 更新地址类型选项
    const sel=document.getElementById('addrType');
    sel.innerHTML=cfg.addrTypes.map((t,i)=>`<option value="${t}">${cfg.addrLabels[i]}</option>`).join('');
    // 更新主题色
    document.documentElement.style.setProperty('--coin-color',cfg.color);
    // 更新标题
    document.getElementById('coinTitle').textContent=cfg.label+' 靓号生成器';
    document.getElementById('resultAddrLabel').textContent=cfg.label+' 地址';
    // 字符集提示
    const hint=document.getElementById('charsetHint');
    if(hint){if(cfg.charset){hint.style.display='block';hint.textContent='⚠️ 该链地址字符集限制：仅支持 '+cfg.charset;}else{hint.style.display='none';}}
}

// ── 生成控制 ──────────────────────────────────────────────────────────────────
function startGenerate(){
    if(isGenerating)return;
    const targetStr=document.getElementById('targetStr').value.trim();
    const matchMode=document.getElementById('matchMode').value;
    const addrType=document.getElementById('addrType').value;
    const threadCount=parseInt(document.getElementById('threads').value);
    const excludeChars=document.getElementById('excludeChars').value.trim();
    if(matchMode==='regex'&&targetStr){try{new RegExp(targetStr);}catch(e){layer.msg('正则表达式格式错误');return;}}
    isGenerating=true;totalAttempts=0;lastAttempts=0;foundResult=null;
    startTime=Date.now();lastUpdateTime=startTime;
    document.getElementById('startBtn').style.display='none';
    document.getElementById('stopBtn').style.display='inline-block';
    document.getElementById('statsBox').classList.add('show');
    document.getElementById('resultBox').classList.remove('show');
    document.getElementById('status').textContent='生成中 ('+threadCount+' 线程)';
    const config={coin:currentCoin,targetStr,matchMode,addrType,excludeChars:excludeChars.toLowerCase()};
    for(let i=0;i<threadCount;i++)launchWorker(config);
    updateInterval=setInterval(updateStats,300);
}

function stopGenerate(){
    isGenerating=false;
    if(updateInterval){clearInterval(updateInterval);updateInterval=null;}
    workers.forEach(w=>w.terminate());workers=[];
    document.getElementById('startBtn').style.display='inline-block';
    document.getElementById('stopBtn').style.display='none';
    document.getElementById('status').textContent=foundResult?'✓ 生成成功':'已停止';
    updateStats();
}

function launchWorker(config){
    const workerUrl=window.location.origin+'/app/vanity/static/js/worker.js';
    const worker=new Worker(workerUrl);
    worker.onmessage=function(e){
        const d=e.data;
        if(d.type==='progress')totalAttempts+=d.n;
        else if(d.type==='found'){totalAttempts+=d.n;if(!foundResult){foundResult=d;stopGenerate();showResult(d);}}
        else if(d.type==='error'){layer.msg('错误: '+d.message,{icon:2});stopGenerate();}
    };
    worker.onerror=function(err){layer.msg('Worker加载失败: '+err.message,{icon:2});stopGenerate();};
    worker.postMessage(config);workers.push(worker);
}

function updateStats(){
    const now=Date.now(),elapsed=Math.floor((now-startTime)/1000);
    const avg=elapsed>0?Math.floor(totalAttempts/elapsed):0;
    const dt=(now-lastUpdateTime)/1000;
    const inst=dt>0?Math.floor((totalAttempts-lastAttempts)/dt):0;
    lastAttempts=totalAttempts;lastUpdateTime=now;
    document.getElementById('attempts').textContent=totalAttempts.toLocaleString();
    document.getElementById('speed').textContent=inst.toLocaleString()+' 次/秒';
    document.getElementById('avgSpeed').textContent=avg.toLocaleString()+' 次/秒';
    document.getElementById('elapsed').textContent=formatTime(elapsed);
    const tgt=document.getElementById('targetStr').value.trim();
    document.getElementById('estimated').textContent=(tgt&&avg>0)?formatTime(Math.floor(Math.pow(58,tgt.length)/avg)):'—';
}

function formatTime(s){if(s<60)return s+' 秒';if(s<3600)return Math.floor(s/60)+' 分 '+(s%60)+' 秒';if(s<86400)return Math.floor(s/3600)+' 时 '+Math.floor((s%3600)/60)+' 分';return Math.floor(s/86400)+' 天 '+Math.floor((s%86400)/3600)+' 时';}

function showResult(r){
    document.getElementById('resultAddress').textContent=r.address;
    document.getElementById('resultWIF').textContent=r.privateKey;
    document.getElementById('resultHex').textContent=r.privateKeyHex;
    document.getElementById('resultBox').classList.add('show');
    layer.msg('🎉 生成成功！请立即保存私钥',{icon:1,time:4000});
}

function copyText(id){
    const t=document.getElementById(id).textContent;
    if(navigator.clipboard){navigator.clipboard.writeText(t).then(()=>layer.msg('复制成功'));}
    else{const ta=document.createElement('textarea');ta.value=t;document.body.appendChild(ta);ta.select();document.execCommand('copy');document.body.removeChild(ta);layer.msg('复制成功');}
}

document.addEventListener('DOMContentLoaded',()=>{
    initCoinTabs();
    selectCoin('btc');
});
