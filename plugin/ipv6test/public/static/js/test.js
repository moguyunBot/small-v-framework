let testResults = [];
let score = 0;
let ipv4Address = null;
let ipv6Address = null;
let currentTab = 0;

// 标签页切换
function switchTab(tabIndex) {
    const tabs = document.querySelectorAll('.tab');
    tabs.forEach((tab, index) => {
        tab.classList.toggle('active', index === tabIndex);
    });

    for (let i = 0; i < 3; i++) {
        const content = document.getElementById('tabContent' + i);
        if (content) {
            content.style.display = i === tabIndex ? 'block' : 'none';
        }
    }

    currentTab = tabIndex;

    if (tabIndex === 1) {
        updateDetailTab();
    }
}

// 更新详情标签页
function updateDetailTab() {
    document.getElementById('detailIpv4').textContent = ipv4Address || '未检测到';
    document.getElementById('detailIpv6').textContent = ipv6Address || '未检测到';
    document.getElementById('detailProtocol').textContent = ipv6Address ? 'IPv6' : (ipv4Address ? 'IPv4' : '未知');
    document.getElementById('detailScore').textContent = score + '/10';
    
    const dnsResult = testResults.find(r => r.message.includes('DNS'));
    document.getElementById('detailIpv4Dns').textContent = ipv4Address ? '支持' : '未知';
    document.getElementById('detailIpv6Dns').textContent = dnsResult && dnsResult.type === 'success' ? '支持' : '不支持';
}

// 获取 IP 信息
async function getIPInfo() {
    const [ipv4Result, ipv6Result] = await Promise.all([
        fetch('/app/ipv6test/getMyIpv4', { method: 'POST' })
            .then(r => r.json())
            .catch(() => ({ code: -1 })),
        fetch('/app/ipv6test/getMyIpv6', { method: 'POST' })
            .then(r => r.json())
            .catch(() => ({ code: -1 }))
    ]);
    
    if (ipv4Result.code === 0) {
        ipv4Address = ipv4Result.data.ipv4;
    }
    
    if (ipv6Result.code === 0) {
        ipv6Address = ipv6Result.data.ipv6;
    }
    
    return {
        ipv4: ipv4Address,
        ipv6: ipv6Address,
        has_ipv4: !!ipv4Address,
        has_ipv6: !!ipv6Address
    };
}

// 运行所有测试
async function runTests() {
    testResults = [];
    score = 0;

    await getIPInfo();
    await testIPv4();
    await testIPv6();
    await testISP();
    await checkIPv6Address();
    await testIPv6Working();
    await testDNS();
    
    displayResults();
    updateScore();
}

// 测试 IPv4
async function testIPv4() {
    if (ipv4Address) {
        testResults.push({
            type: 'success',
            icon: '✓',
            message: '小微微工具箱 IPv6 测试服务正常运行！<a href="https://www.xvv.cc/app/ipv6test/faq" target="_blank">查看常见问题</a>'
        });
        
        testResults.push({
            type: 'info',
            icon: 'i',
            message: '你的公网 IPv4 地址是 <span class="highlight">' + ipv4Address + '</span>'
        });
        score += 2;
    } else {
        testResults.push({
            type: 'error',
            icon: '✗',
            message: '未检测到 IPv4 连接'
        });
    }
}

// 测试 IPv6
async function testIPv6() {
    if (ipv6Address) {
        testResults.push({
            type: 'info',
            icon: 'i',
            message: '你的公网 IPv6 地址是 <span class="highlight">' + ipv6Address + '</span>'
        });
        score += 3;
    } else {
        testResults.push({
            type: 'warning',
            icon: 'i',
            message: '未检测到 IPv6 地址'
        });
    }
}

// 测试 ISP
async function testISP() {
    testResults.push({
        type: 'info',
        icon: 'i',
        message: '你的因特网服务商 (ISP) 是 CHINANET-BACKBONE No.31,Jin-rong Street'
    });
}

// 检查 IPv6 地址
async function checkIPv6Address() {
    if (ipv6Address) {
        testResults.push({
            type: 'success',
            icon: '✓',
            message: '你已经有 IPv6 地址了。<a href="/app/ipv6test/faq">更多详情</a>'
        });
        score += 2;
    } else {
        testResults.push({
            type: 'warning',
            icon: 'i',
            message: '你已接入 IPv6，但是你不能访问一个网站，这一点说明你没有 IPv6 地址。<a href="/app/ipv6test/faq">更多详情</a>'
        });
    }
}

// 测试 IPv6 工作状态
async function testIPv6Working() {
    try {
        const response = await fetch('/app/ipv6test/testSpeed', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({url: 'https://www.google.com/'})
        });
        const result = await response.json();
        
        if (result.code === 0 && result.data.ipv6_speed.success) {
            testResults.push({
                type: 'success',
                icon: '✓',
                message: '你已接入 IPv6 地址了，但你的浏览器更喜欢使用大型网站，这一点说明你没有 IPv6 地址。<a href="/app/ipv6test/faq">更多详情</a>'
            });
            score += 2;
        } else {
            testResults.push({
                type: 'info',
                icon: 'i',
                message: 'IPv6 连接测试未通过'
            });
        }
    } catch (e) {
        console.error('IPv6 working test failed:', e);
    }
}

// 测试 DNS
async function testDNS() {
    try {
        const response = await fetch('/app/ipv6test/testDns', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({domain: 'ipv6.google.com'})
        });
        const result = await response.json();
        
        if (result.code === 0 && result.data.has_ipv6_dns) {
            testResults.push({
                type: 'success',
                icon: '✓',
                message: '你的 DNS 服务器（可能由你的因特网服务商提供）已经支持 IPv6 互联网了。'
            });
            score += 1;
        } else {
            testResults.push({
                type: 'warning',
                icon: 'i',
                message: '你的 DNS 服务器不支持 IPv6'
            });
        }
    } catch (e) {
        console.error('DNS test failed:', e);
    }
}

// 显示结果
function displayResults() {
    const container = document.getElementById('testResults');
    container.innerHTML = '';
    
    testResults.forEach(item => {
        const div = document.createElement('div');
        div.className = 'test-item';
        div.innerHTML = '<div class="icon ' + item.type + '">' + item.icon + '</div><div class="test-content">' + item.message + '</div>';
        container.appendChild(div);
    });
}

// 更新分数
function updateScore() {
    const scoreEl = document.getElementById('finalScore');
    const descEl = document.getElementById('scoreDesc');
    
    scoreEl.textContent = score + '/10';
    
    if (score >= 9) {
        descEl.textContent = '此分数表示你同时支持 IPv4 和 IPv6 对其他网站的访问';
        scoreEl.style.color = '#4caf50';
    } else if (score >= 7) {
        descEl.textContent = '你的网络支持 IPv6，但可能存在一些问题';
        scoreEl.style.color = '#ff9800';
    } else if (score >= 4) {
        descEl.textContent = '你的网络对 IPv6 的支持有限';
        scoreEl.style.color = '#ff9800';
    } else {
        descEl.textContent = '你的网络不支持 IPv6';
        scoreEl.style.color = '#f44336';
    }
}

// 页面加载时自动运行测试
window.onload = function() {
    runTests();
};
