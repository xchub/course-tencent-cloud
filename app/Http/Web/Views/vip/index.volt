{% extends 'templates/full.volt' %}

{% block content %}

    <div class="vip-header">会员权益</div>

    <div class="vip-reason-list container">
        <span class="layui-badge reason-badge">好课畅学</span>
        <span class="layui-badge reason-badge">会员折扣</span>
        <span class="layui-badge reason-badge">高清视频</span>
        <span class="layui-badge reason-badge">广告免疫</span>
        <span class="layui-badge reason-badge">会员标识</span>
        <span class="layui-badge reason-badge">贴心服务</span>
    </div>

    <div class="vip-header">开通会员</div>

    <div class="vip-option-list clearfix">
        {% for option in vip_option_list %}
            {% set order_url = url({'for':'web.order.confirm'},{'item_id':option.id,'item_type':'vip'}) %}
            <div class="vip-option-card">
                <div class="title">{{ option.title }}</div>
                <div class="price">￥{{ option.price }}</div>
                <div class="order"><a class="layui-btn layui-btn-sm layui-bg-red" href="{{ order_url }}">立即开通</a></div>
            </div>
        {% endfor %}
    </div>

    {% set courses_url = url({'for':'web.vip.courses'}) %}
    {% set users_url = url({'for':'web.vip.users'}) %}

    <div class="container">
        <div class="layui-tab layui-tab-brief user-tab">
            <ul class="layui-tab-title">
                <li class="layui-this">课程</li>
                <li>成员</li>
            </ul>
            <div class="layui-tab-content">
                <div class="layui-tab-item layui-show" id="tab-courses" data-url="{{ courses_url }}"></div>
                <div class="layui-tab-item" id="tab-users" data-url="{{ users_url }}"></div>
            </div>
        </div>
    </div>

{% endblock %}

{% block inline_js %}

    <script>
        if ($('#tab-courses').length > 0) {
            var $tabCourses = $('#tab-courses');
            helper.ajaxLoadHtml($tabCourses.attr('data-url'), $tabCourses.attr('id'));
        }
        if ($('#tab-users').length > 0) {
            var $tabUsers = $('#tab-users');
            helper.ajaxLoadHtml($tabUsers.attr('data-url'), $tabUsers.attr('id'));
        }
    </script>

{% endblock %}