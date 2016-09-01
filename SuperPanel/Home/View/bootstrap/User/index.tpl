<!DOCTYPE html>
<html lang="<{:cookie('locale')}>">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>用户面板 - <{$site_name}></title>
    
    <include file="Home@bootstrap/User/head" />
    <style>
        .auto-hide span{
            display:none;
        }
    </style>
</head>

<body>

    <div id="wrapper">
        <include file="Home@bootstrap/User/nav" />

        <!-- Page Content -->
        <div id="page-wrapper" style="">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-12">
                        <h1 class="page-header">用户中心</h1>
                    </div>
                    <!-- /.col-lg-12 -->
                    <div class="col-md-6">
                        <if condition="!empty($announcement['user_home'])">
                        <!--仅在有公告时显示面板-->
                        <div class="panel panel-primary">
                            <div class="panel-heading">公告</div>
                            <div class="panel-body">
                                <{$announcement['user_home']}>
                            </div>
                        </div>
                        </if>
                        <div class="panel panel-green">
                            <div class="panel-heading"><i class="fa fa-edit"></i> 签到</div>
                            <div class="panel-body text-center">
                                <strong>今天你签到了么？</strong>
                                <p>上次签到时间：<code><{$userinfo['last_check_in_time']?date('Y-m-d H:i:s', $userinfo['last_check_in_time']):"从未签到"}></code></p>
                            </div>
                            <div class="panel-footer text-right">
                                <button class="btn btn-warning">我要签到</button>
                            </div>
                        </div>
                    </div>
                    <!--/.col-md-6-->
                    <div class="col-md-6">
                        <div class="panel panel-info">
                            <div class="panel-heading">使用情况</div>
                            <div class="panel-body">
                                <div class="row">
                                    <div class="col-lg-6 col-xs-12">
                                        <canvas id="myChart" width="100" height="100"></canvas>
                                    </div>
                                    <div class="col-md-12"><p>上次使用时间：<code><{$userinfo['t']?date('Y-m-d H:i:s', $userinfo['t']):"从未使用"}></code></p></div>
                                </div>
                            </div>
                            <div class="panel-footer"><button class="btn btn-warning">查看详情</button></div>
                        </div>
                        <div class="panel panel-red">
                            <div class="panel-heading">连接信息</div>
                            <div class="panel-body">
                                <small class="text-warning">鼠标放在表格空白处就可以显示隐藏的端口和密码哦</small>
                                <table class="table">
                                    <tbody>
                                        <tr>
                                            <td>端口</td>
                                            <td class="auto-hide"><span><{$userinfo['port']}></span></td>
                                        </tr>
                                        <tr>
                                            <td>密码</td>
                                            <td class="auto-hide"><span><{$userinfo['passwd']}></span></td>
                                        </tr>
                                        <tr>
                                            <td>加密方式</td>
                                            <td><{$userinfo['method']}></td>
                                        </tr>
                                        <tr>
                                            <td>协议</td>
                                            <td><{$userinfo['protocol']}></td>
                                        </tr>
                                        <tr>
                                            <td>混淆</td>
                                            <td><{$userinfo['obfs']}></td>
                                        </tr>
                                    </tbody>
                                </table>
                                <small class="text-info">*加密方式、协议和混淆都可以在用户设置中修改</small>
                            </div>
                            <div class="panel-footer"><button class="btn btn-warning">查看我的节点</button></div>
                        </div>
                    </div>
                    <!--/.ol-md-6-->
                </div>
                <!-- /.row -->

            </div>
            <!-- /.container-fluid -->

        </div>
        <!-- /#page-wrapper -->
    </div>
    <!-- /#wrapper -->
<include file="Home@bootstrap/Public/footer" />
</body>

<include file="Home@bootstrap/User/js" />
    <script src="//cdn.bootcss.com/Chart.js/2.2.1/Chart.min.js"></script>
<script>
$(document).ready(function(){
        $(".auto-hide").mouseover(function(){
            $(this).children().show();
        });
        $(".auto-hide").mouseleave(function(){
            $(this).children().hide();
        });
    });


var data = {
    labels: [
        "已用流量",
        "可用流量"
    ],
    datasets: [
        {
            data: [300, 50],
            backgroundColor: [
                "#FF6384",
                "#00FF00"
            ],
            hoverBackgroundColor: [
                "#FF6384",
                "#00FF00"
            ]
        }]
};

var ctx = $("#myChart");
var myChart = new Chart(ctx, {
    type:'doughnut',
    data:data,
    options:{
        tooltips: {
            enabled: true,
            tooltipTemplate: "<%if (label){%><%=label%>: <%}%><strong><%= value %></strong>%"
          }
        }
    });
</script>
</html>
