'use strict';
// 统一 worker — 通过 cfg.coin 区分链
importScripts(self.location.origin + '/app/vanity/static/js/crypto-common.js');

// ── XRP Base58 (不同字母表) ────────────────────────────────────────────────────
const XRP58='rpshnaf39wBUDNEGHJKLM4PQRST7VWXYZ2bcdeCg65jkm8oFqi1tuvAxyz';
function xrpB58enc(bytes){let n=BigInt('0x'+Array.from(bytes).map(b=>b.toString(16).padStart(2,'0')).join(''));let r='';while(n>0n){r=XRP58[Number(n%58n)]+r;n=n/58n;}for(const b of bytes){if(b!==0)break;r=XRP58[0]+r;}return r;}
function xrpB58chk(payload){const h1=sha256(payload),h2=sha256(h1);const full=new Uint8Array(payload.length+4);full.set(payload);full.set(h2.slice(0,4),payload.length);return xrpB58enc(full);}

// ── Stellar Base32+CRC16 ────────────────────────────────────────────────────
const XLM32='ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
function xlm32enc(bytes){let bits=0,val=0,out='';for(const b of bytes){val=(val<<8)|b;bits+=8;while(bits>=5){out+=XLM32[(val>>(bits-5))&31];bits-=5;}}if(bits>0)out+=XLM32[(val<<(5-bits))&31];while(out.length%8!==0)out+='=';return out;}
function crc16(bytes){let crc=0;for(const b of bytes){crc^=b<<8;for(let i=0;i<8;i++)crc=(crc&0x8000)?(crc<<1)^0x1021:(crc<<1);crc&=0xFFFF;}return crc;}
function xlmAddress(pub32){const payload=new Uint8Array(35);payload[0]=0x06;payload[1]=0x1E;payload.set(pub32,2);const crc=crc16(payload.slice(0,34));payload[33]=crc&0xff;payload[34]=(crc>>8)&0xff;return xlm32enc(payload).replace(/=/g,'');}

// ── SS58 (Polkadot) ──────────────────────────────────────────────────────────
function ss58(pubkey,network){
    const prefix=new Uint8Array(1);prefix[0]=network;
    const payload=new Uint8Array(33);payload.set(prefix);payload.set(pubkey,1);
    const preimage=new Uint8Array(payload.length+7);
    [0x53,0x53,0x35,0x38,0x50,0x52,0x45].forEach((v,i)=>preimage[i]=v);
    preimage.set(payload,7);
    const h=sha256(sha256(preimage));
    const full=new Uint8Array(35);full.set(payload);full[33]=h[0];full[34]=h[1];
    let n=BigInt('0x'+Array.from(full).map(b=>b.toString(16).padStart(2,'0')).join(''));
    let r='';while(n>0n){r=B58[Number(n%58n)]+r;n=n/58n;}
    for(const b of full){if(b!==0)break;r='1'+r;}
    return r;
}

// ── 各链地址生成函数 ──────────────────────────────────────────────────────────
const CHAINS={
    btc:{
        makeAddr:(priv,type)=>{
            const pub=privToPub(priv);if(!pub)return null;
            if(type==='bech32'){const h160=ripemd160(sha256(pub));return b32enc('bc',[0,...convBits(h160,8,5,true)]);}
            const ver=type==='p2sh'?0x05:0x00;
            const p=new Uint8Array(21);p[0]=ver;p.set(ripemd160(sha256(pub)),1);return b58chk(p);
        },
        wif:(priv)=>toWIF(priv,0x80),
        skip:1,types:['p2pkh','p2sh','bech32']
    },
    ltc:{
        makeAddr:(priv,type)=>{
            const pub=privToPub(priv);if(!pub)return null;
            if(type==='bech32'){const h160=ripemd160(sha256(pub));return b32enc('ltc',[0,...convBits(h160,8,5,true)]);}
            const ver=type==='p2sh'?0x32:0x30;
            const p=new Uint8Array(21);p[0]=ver;p.set(ripemd160(sha256(pub)),1);return b58chk(p);
        },
        wif:(priv)=>toWIF(priv,0xB0),
        skip:1,types:['p2pkh','p2sh','bech32']
    },
    doge:{
        makeAddr:(priv)=>{
            const pub=privToPub(priv);if(!pub)return null;
            const p=new Uint8Array(21);p[0]=0x1E;p.set(ripemd160(sha256(pub)),1);return b58chk(p);
        },
        wif:(priv)=>toWIF(priv,0x9E),
        skip:1,types:['p2pkh']
    },
    xrp:{
        makeAddr:(priv)=>{
            const pub=privToPub(priv);if(!pub)return null;
            const h160=ripemd160(sha256(pub));const p=new Uint8Array(21);p[0]=0x00;p.set(h160,1);return xrpB58chk(p);
        },
        wif:(priv)=>toHex(priv),
        skip:1,types:['default']
    },
    atom:{
        makeAddr:(priv)=>{
            const pub=privToPub(priv);if(!pub)return null;
            return b32enc('cosmos',[0,...convBits(ripemd160(sha256(pub)),8,5,true)]);
        },
        wif:(priv)=>toHex(priv),
        skip:7,types:['default']
    },
    sol:{
        makeAddr:(priv)=>{
            const pub=ed25519Pub(priv);if(!pub)return null;
            return b58enc(pub);
        },
        wif:(priv)=>toHex(priv),
        skip:0,types:['default']
    },
    xlm:{
        makeAddr:(priv)=>{
            const pub=ed25519Pub(priv);if(!pub)return null;
            return xlmAddress(pub);
        },
        wif:(priv)=>toHex(priv),
        skip:1,types:['default']
    },
    ada:{
        makeAddr:(priv)=>{
            const pub=ed25519Pub(priv);if(!pub)return null;
            const h=sha256(pub).slice(0,28);
            const payload=new Uint8Array(29);payload[0]=0x61;payload.set(h,1);
            return b32enc('addr',[...convBits(payload,8,5,true)]);
        },
        wif:(priv)=>toHex(priv),
        skip:5,types:['default']
    },
    dot:{
        makeAddr:(priv)=>{
            const pub=ed25519Pub(priv);if(!pub)return null;
            return ss58(pub,0);
        },
        wif:(priv)=>toHex(priv),
        skip:1,types:['default']
    },
    apt:{
        makeAddr:(priv)=>{
            const pub=ed25519Pub(priv);if(!pub)return null;
            const input=new Uint8Array(33);input.set(pub);input[32]=0x00;
            return '0x'+Array.from(sha256(input)).map(b=>b.toString(16).padStart(2,'0')).join('');
        },
        wif:(priv)=>toHex(priv),
        skip:2,types:['default']
    },
    tron:{
        makeAddr:(priv)=>{
            const pub=privToPub(priv);if(!pub)return null;
            const uncompressed=expandPub(pub);
            const h=keccak256(uncompressed.slice(1));
            const p=new Uint8Array(21);p[0]=0x41;p.set(h.slice(12),1);return b58chk(p);
        },
        wif:(priv)=>toHex(priv),
        skip:1,types:['default']
    },
    eth:{
        makeAddr:(priv)=>{
            const pub=privToPub(priv);if(!pub)return null;
            const uncompressed=expandPub(pub);
            const h=keccak256(uncompressed.slice(1));
            return '0x'+Array.from(h.slice(12)).map(b=>b.toString(16).padStart(2,'0')).join('');
        },
        wif:(priv)=>toHex(priv),
        skip:2,types:['default']
    },
};

// ── Keccak-256 (ETH/TRON 需要) ──────────────────────────────────────────────
function keccak256(msg){
    const M64=0xFFFFFFFFFFFFFFFFn;
    const RC=[0x0000000000000001n,0x0000000000008082n,0x800000000000808An,0x8000000080008000n,0x000000000000808Bn,0x0000000080000001n,0x8000000080008081n,0x8000000000008009n,0x000000000000008An,0x0000000000000088n,0x0000000080008009n,0x000000008000000An,0x000000008000808Bn,0x800000000000008Bn,0x8000000000008089n,0x8000000000008003n,0x8000000000008002n,0x8000000000000080n,0x000000000000800An,0x800000008000000An,0x8000000080008081n,0x8000000000008080n,0x0000000080000001n,0x8000000080008008n];
    const ROT=[0,1,62,28,27,36,44,6,55,20,3,10,43,25,39,41,45,15,21,8,18,2,61,56,14];
    const PI=[0,10,20,5,15,16,1,11,21,6,7,17,2,12,22,23,8,18,3,13,14,24,9,19,4];
    const rot=(x,n)=>n===0n?x:((x<<n)|(x>>(64n-n)))&M64;
    const data=Array.from(msg instanceof Uint8Array?msg:new Uint8Array(msg));
    // padding
    data.push(0x01);
    while(data.length%136!==0)data.push(0x00);
    data[data.length-1]^=0x80;
    // absorb
    const S=new Array(25).fill(0n);
    for(let b=0;b<data.length;b+=136){
        for(let i=0;i<17;i++){
            let v=0n;
            for(let j=7;j>=0;j--)v=(v<<8n)|BigInt(data[b+i*8+j]);
            S[i]^=v;
        }
        // Keccak-f[1600]
        for(let r=0;r<24;r++){
            // Theta
            const C=[S[0]^S[5]^S[10]^S[15]^S[20],S[1]^S[6]^S[11]^S[16]^S[21],S[2]^S[7]^S[12]^S[17]^S[22],S[3]^S[8]^S[13]^S[18]^S[23],S[4]^S[9]^S[14]^S[19]^S[24]];
            const D=[C[4]^rot(C[1],1n),C[0]^rot(C[2],1n),C[1]^rot(C[3],1n),C[2]^rot(C[4],1n),C[3]^rot(C[0],1n)];
            for(let i=0;i<25;i++)S[i]^=D[i%5];
            // Rho + Pi
            const B=new Array(25);
            for(let i=0;i<25;i++)B[PI[i]]=rot(S[i],BigInt(ROT[i]));
            // Chi
            for(let i=0;i<25;i+=5){
                const b0=B[i],b1=B[i+1],b2=B[i+2],b3=B[i+3],b4=B[i+4];
                S[i]  =b0^((~b1)&M64&b2);
                S[i+1]=b1^((~b2)&M64&b3);
                S[i+2]=b2^((~b3)&M64&b4);
                S[i+3]=b3^((~b4)&M64&b0);
                S[i+4]=b4^((~b0)&M64&b1);
            }
            // Iota
            S[0]^=RC[r];
        }
    }
    // squeeze
    const out=new Uint8Array(32);
    for(let i=0;i<4;i++){let v=S[i];for(let j=0;j<8;j++){out[i*8+j]=Number(v&0xffn);v>>=8n;}}
    return out;
}
// 将压缩公钥展开为非压缩公钥
function expandPub(compressed){
    const x=BigInt('0x'+Array.from(compressed.slice(1)).map(b=>b.toString(16).padStart(2,'0')).join(''));
    const isOdd=(compressed[0]===0x03);
    // y^2 = x^3 + 7 (mod FP)
    const y2=fm(fm(fm(x*x)*x)+7n);
    // sqrt via y = y2^((FP+1)/4) mod FP  (works because FP ≡ 3 mod 4)
    let y=fm(epow(y2,(FP+1n)/4n));
    if((y&1n)!==(isOdd?1n:0n))y=fm(FP-y);
    const out=new Uint8Array(65);out[0]=0x04;
    let xv=x,yv=y;
    for(let i=31;i>=0;i--){out[1+i]=Number(xv&0xffn);xv>>=8n;out[33+i]=Number(yv&0xffn);yv>>=8n;}
    return out;
}
function epow(b,e){let r=1n;b=fm(b);while(e>0n){if(e&1n)r=fm(r*b);b=fm(b*b);e>>=1n;}return r;}

// ── 主循环 ────────────────────────────────────────────────────────────────────
let _running=false;
self.onmessage=function(e){
    const cfg=e.data;
    const coin=cfg.coin||'btc';
    const chain=CHAINS[coin];
    if(!chain){postMessage({type:'error',message:'Unknown coin: '+coin});return;}
    const tgt=cfg.targetStr||'',exc=(cfg.excludeChars||'').toLowerCase();
    const tgtL=tgt.toLowerCase(),excArr=exc?exc.split(''):[];
    const skip=chain.skip,type=cfg.addrType||chain.types[0];
    const BATCH=20,batchBuf=new Uint8Array(32*BATCH);
    let attempts=0,reported=0;
    _running=true;
    function runBatch(){
        if(!_running)return;
        crypto.getRandomValues(batchBuf);
        for(let b=0;b<BATCH;b++){
            attempts++;
            const seed=new Uint8Array(batchBuf.subarray(b*32,b*32+32));
            let addr;
            try{addr=chain.makeAddr(seed,type);}catch(err){
                postMessage({type:'error',message:coin+' makeAddr error: '+err.message});
                _running=false;return;
            }
            if(!addr)continue;
            const addrL=addr.toLowerCase(),body=addrL.slice(skip);
            if(excArr.length&&excArr.some(c=>body.includes(c)))continue;
            let ok=!tgt;
            if(tgt){switch(cfg.matchMode){
                case'prefix':ok=body.startsWith(tgtL);break;
                case'suffix':ok=addrL.endsWith(tgtL);break;
                case'contains':ok=addrL.includes(tgtL);break;
                case'regex':try{ok=new RegExp(tgt,'i').test(addr);}catch(_){ok=false;}break;
                default:ok=body.startsWith(tgtL);
            }}
            if(ok){
                _running=false;
                const privateKey=chain.wif(seed),privateKeyHex=toHex(seed);
                postMessage({type:'progress',n:attempts-reported});
                postMessage({type:'found',address:addr,privateKey,privateKeyHex,n:0});
                return;
            }
        }
        postMessage({type:'progress',n:attempts-reported});reported=attempts;
        setTimeout(runBatch,0);
    }
    setTimeout(runBatch,0);
};
