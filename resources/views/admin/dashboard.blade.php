@extends('admin.master_layout')
@section('title')
    <title>{{ __('Dashboard') }}</title>
@endsection
@section('admin-content')
    <!-- Main Content -->
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>{{ __('Dashbaord') }}</h1>
            </div>

            @php
                $monthsArray = [
                    1 => __('January'),
                    2 => __('February'),
                    3 => __('March'),
                    4 => __('April'),
                    5 => __('May'),
                    6 => __('June'),
                    7 => __('July'),
                    8 => __('August'),
                    9 => __('September'),
                    10 => __('October'),
                    11 => __('November'),
                    12 => __('December'),
                ];
            @endphp

            <div class="section-body">
                <!-- Settings aur Staff Cards -->
                <div class="row mb-4">
                    <!-- <div class="col-lg-6 col-md-6 col-sm-12">
                                        <a href="{{ url('admin/settings') }}" style="text-decoration: none;">
                                            <div class="card" style="background: #FE7701; border-radius: 15px; padding: 25px; box-shadow: 0 4px 15px rgba(93, 120, 238, 0.3); transition: all 0.3s; hover:transform: translateY(-5px);">
                                                <div style="display: flex; align-items: center;">
                                                    <div style="background: rgba(255,255,255,0.2); width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 20px;">
                                                        <i class="fas fa-cog" style="color: white; font-size: 28px;"></i>
                                                    </div>
                                                    <div>
                                                        <h4 style="color: white; margin: 0; font-weight: 600; font-size: 24px;">Settings</h4>
                                                        <p style="color: rgba(255,255,255,0.9); margin: 5px 0 0; font-size: 14px;">System configuration and preferences</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </a>
                                    </div> -->

                    @can('staff.view')
                        <div class="col-lg-6 col-md-6 col-sm-12">
                            <a href="{{ url('admin/staff') }}" style="text-decoration: none;">
                                <div class="card"
                                    style="background: #FE7701; border-radius: 15px; padding: 25px; box-shadow: 0 4px 15px rgba(93, 120, 238, 0.3); transition: all 0.3s; hover:transform: translateY(-5px);">
                                    <div style="display: flex; align-items: center;">
                                        <div
                                            style="background: rgba(255,255,255,0.2); width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 20px;">
                                            <i class="fas fa-users" style="color: white; font-size: 28px;"></i>
                                        </div>
                                        <div>
                                            <h4 style="color: white; margin: 0; font-weight: 600; font-size: 24px;">Agents</h4>
                                            <p style="color: rgba(255,255,255,0.9); margin: 5px 0 0; font-size: 14px;">Manage
                                                agents</p>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    @endcan
                </div>

                <!-- Static Dashboard Content -->
                <!-- <div class="row">
                                    <div class="col-lg-4 col-md-4 col-sm-12">
                                        <div class="card card-statistic-2">
                                            <div class="card-stats">
                                                <div class="card-stats-title">Total Users</div>
                                                <div class="card-stats-items">
                                                    <div class="card-stats-item">
                                                        <div class="card-stats-item-count">10</div>
                                                        <div class="card-stats-item-label">Active</div>
                                                    </div>
                                                    <div class="card-stats-item">
                                                        <div class="card-stats-item-count">2</div>
                                                        <div class="card-stats-item-label">Inactive</div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="card-icon shadow-primary bg-primary">
                                                <i class="fas fa-users"></i>
                                            </div>
                                            <div class="card-wrap">
                                                <div class="card-header">
                                                    <h4>Total Users</h4>
                                                </div>
                                                <div class="card-body">
                                                    12
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-lg-4 col-md-4 col-sm-12">
                                        <div class="card card-statistic-2">
                                            <div class="card-chart">
                                                <canvas id="balance-chart" height="80"></canvas>
                                            </div>
                                            <div class="card-icon shadow-primary bg-primary">
                                                <i class="fas fa-dollar-sign"></i>
                                            </div>
                                            <div class="card-wrap">
                                                <div class="card-header">
                                                    <h4>Total Revenue</h4>
                                                </div>
                                                <div class="card-body">
                                                    $12,500
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-lg-4 col-md-4 col-sm-12">
                                        <div class="card card-statistic-2">
                                            <div class="card-chart">
                                                <canvas id="sales-chart" height="80"></canvas>
                                            </div>
                                            <div class="card-icon shadow-primary bg-primary">
                                                <i class="fas fa-shopping-bag"></i>
                                            </div>
                                            <div class="card-wrap">
                                                <div class="card-header">
                                                    <h4>Total Orders</h4>
                                                </div>
                                                <div class="card-body">
                                                    1,245
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div> -->

                <!-- Add more static dashboard content as needed -->
                <!-- <div class="row mt-4">
                                    <div class="col-12">
                                        <div class="card">
                                            <div class="card-header">
                                                <h4>Recent Activity</h4>
                                            </div>
                                            <div class="card-body">
                                                <p>Dashboard content loaded successfully.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div> -->
            </div>
        </section>
    </div>
@endsection

@push('js')
    <script src="{{ asset('website/js/chart.min.js') }}"></script>

    <script>
        // Static data for charts
        var balance_chart = document.getElementById("balance-chart").getContext('2d');

        var balance_chart_bg_color = balance_chart.createLinearGradient(0, 0, 0, 70);
        balance_chart_bg_color.addColorStop(0, 'rgba(63,82,227,.2)');
        balance_chart_bg_color.addColorStop(1, 'rgba(63,82,227,0)');

        var myChart = new Chart(balance_chart, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                datasets: [{
                    label: '{{ __('Balance') }}',
                    data: [1200, 1500, 1800, 2200, 2500, 2800, 3100, 3500, 3800, 4200, 4500, 5000],
                    backgroundColor: balance_chart_bg_color,
                    borderWidth: 3,
                    borderColor: 'rgba(63,82,227,1)',
                    pointBorderWidth: 0,
                    pointBorderColor: 'transparent',
                    pointRadius: 3,
                    pointBackgroundColor: 'transparent',
                    pointHoverBackgroundColor: 'rgba(63,82,227,1)',
                }]
            },
            options: {
                layout: {
                    padding: {
                        bottom: -1,
                        left: -1
                    }
                },
                legend: {
                    display: false
                },
                scales: {
                    yAxes: [{
                        gridLines: {
                            display: false,
                            drawBorder: false,
                        },
                        ticks: {
                            beginAtZero: true,
                            display: false
                        }
                    }],
                    xAxes: [{
                        gridLines: {
                            drawBorder: false,
                            display: false,
                        },
                        ticks: {
                            display: false
                        }
                    }]
                },
            }
        });

        var sales_chart = document.getElementById("sales-chart").getContext('2d');

        var sales_chart_bg_color = sales_chart.createLinearGradient(0, 0, 0, 80);
        sales_chart_bg_color.addColorStop(0, 'rgba(63,82,227,.2)');
        sales_chart_bg_color.addColorStop(1, 'rgba(63,82,227,0)');

        var myChart = new Chart(sales_chart, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                datasets: [{
                    label: 'Sales',
                    data: [50, 65, 80, 95, 110, 125, 140, 155, 170, 185, 200, 215],
                    borderWidth: 2,
                    backgroundColor: sales_chart_bg_color,
                    borderWidth: 3,
                    borderColor: 'rgba(63,82,227,1)',
                    pointBorderWidth: 0,
                    pointBorderColor: 'transparent',
                    pointRadius: 3,
                    pointBackgroundColor: 'transparent',
                    pointHoverBackgroundColor: 'rgba(63,82,227,1)',
                }]
            },
            options: {
                layout: {
                    padding: {
                        bottom: -1,
                        left: -1
                    }
                },
                legend: {
                    display: false
                },
                scales: {
                    yAxes: [{
                        gridLines: {
                            display: false,
                            drawBorder: false,
                        },
                        ticks: {
                            beginAtZero: true,
                            display: false
                        }
                    }],
                    xAxes: [{
                        gridLines: {
                            drawBorder: false,
                            display: false,
                        },
                        ticks: {
                            display: false
                        }
                    }]
                },
            }
        });
    </script>
@endpush