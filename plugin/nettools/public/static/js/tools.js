// Ping 测试
async function testPing() {
    const host = document.getElementById('pingHost').value;
    const btn = document.getElementById('pingBtn');
    const result = document.getElementById('pingResult');
    
    if (!host) {
        layer.msg('请输入主机地址');
        return;
    }
    
    btn.disabled = true;
    btn.innerHTML = '<span class="loading"></span> 测试中...';
    result.classList.remove('show');
    
    try {
        const response = await fetch('/app/nettools/ping', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({host: host})
        });
        const data = await response.json();
        
        if (data.code === 0) {
            result.innerHTML = '<pre>' + data.data.result + '</pre>';
            result.classList.add('show');
        } else {
            layer.msg(data.msg);
        }
    } catch (e) {
        layer.msg('测试失败：' + e.message);
    }
    
    btn.disabled = false;
    btn.textContent = '开始测试';
}

// DNS 查询
async function testDns() {
    const domain = document.getElementById('dnsDomain').value;
    const type = document.getElementById('dnsType').value;
    const btn = document.getElementById('dnsBtn');
    const result = document.getElementById('dnsResult');
    
    if (!domain) {
        layer.msg('请输入域名');
        return;
    }
    
    btn.disabled = true;
    btn.innerHTML = '<span class="loading"></span> 查询中...';
    result.classList.remove('show');
    
    try {
        const response = await fetch('/app/nettools/dns', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({domain: domain, type: type})
        });
        const data = await response.json();
        
        if (data.code === 0) {
            let html = '<pre>';
            if (data.data.records.length > 0) {
                html += JSON.stringify(data.data.records, null, 2);
            } else {
                html += '未找到记录';
            }
            html += '</pre>';
            result.innerHTML = html;
            result.classList.add('show');
        } else {
            layer.msg(data.msg);
        }
    } catch (e) {
        layer.msg('查询失败：' + e.message);
    }
    
    btn.disabled = false;
    btn.textContent = '查询';
}

// Whois 查询
async function testWhois() {
    const domain = document.getElementById('whoisDomain').value;
    const btn = document.getElementById('whoisBtn');
    const result = document.getElementById('whoisResult');
    
    if (!domain) {
        layer.msg('请输入域名');
        return;
    }
    
    btn.disabled = true;
    btn.innerHTML = '<span class="loading"></span> 查询中...';
    result.classList.remove('show');
    
    try {
        const response = await fetch('/app/nettools/whois', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({domain: domain})
        });
        const data = await response.json();
        
        if (data.code === 0) {
            result.innerHTML = '<pre>' + data.data.result + '</pre>';
            result.classList.add('show');
        } else {
            layer.msg(data.msg);
        }
    } catch (e) {
        layer.msg('查询失败：' + e.message);
    }
    
    btn.disabled = false;
    btn.textContent = '查询';
}

// 端口扫描
async function testPort() {
    const host = document.getElementById('portHost').value;
    const port = document.getElementById('portNumber').value;
    const btn = document.getElementById('portBtn');
    const result = document.getElementById('portResult');
    
    if (!host || !port) {
        layer.msg('请输入主机地址和端口');
        return;
    }
    
    btn.disabled = true;
    btn.innerHTML = '<span class="loading"></span> 扫描中...';
    result.classList.remove('show');
    
    try {
        const response = await fetch('/app/nettools/port', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({host: host, port: port})
        });
        const data = await response.json();
        
        if (data.code === 0) {
            const status = data.data.is_open ? '✓ 开放' : '✗ 关闭';
            result.innerHTML = '<pre>端口状态: ' + status + '\n' + data.data.message + '</pre>';
            result.classList.add('show');
        } else {
            layer.msg(data.msg);
        }
    } catch (e) {
        layer.msg('扫描失败：' + e.message);
    }
    
    btn.disabled = false;
    btn.textContent = '扫描';
}
