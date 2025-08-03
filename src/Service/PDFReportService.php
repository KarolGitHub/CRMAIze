<?php

namespace CRMAIze\Service;

use CRMAIze\Core\Application;

class PDFReportService
{
  private Application $app;
  private AnalyticsService $analyticsService;

  public function __construct(Application $app, AnalyticsService $analyticsService)
  {
    $this->app = $app;
    $this->analyticsService = $analyticsService;
  }

  /**
   * Generate comprehensive analytics report
   */
  public function generateAnalyticsReport(): string
  {
    $analytics = $this->analyticsService->getAnalyticsData();
    $chartData = $this->analyticsService->getChartData();

    $html = $this->generateReportHTML($analytics, $chartData);

    return $html;
  }

  /**
   * Generate customer report
   */
  public function generateCustomerReport(): string
  {
    $analytics = $this->analyticsService->getAnalyticsData();

    $html = $this->generateCustomerReportHTML($analytics);

    return $html;
  }

  /**
   * Generate campaign report
   */
  public function generateCampaignReport(): string
  {
    $analytics = $this->analyticsService->getAnalyticsData();

    $html = $this->generateCampaignReportHTML($analytics);

    return $html;
  }

  /**
   * Generate main analytics report HTML
   */
  private function generateReportHTML(array $analytics, array $chartData): string
  {
    $date = date('F j, Y');
    $time = date('H:i:s');

    $html = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>CRMAIze Analytics Report</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; color: #333; }
                .header { text-align: center; border-bottom: 2px solid #1779ba; padding-bottom: 20px; margin-bottom: 30px; }
                .header h1 { color: #1779ba; margin: 0; }
                .header p { color: #666; margin: 5px 0; }
                .section { margin-bottom: 30px; }
                .section h2 { color: #1779ba; border-bottom: 1px solid #ddd; padding-bottom: 10px; }
                .metrics-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0; }
                .metric-card { background: #f8f9fa; border: 1px solid #ddd; border-radius: 8px; padding: 20px; text-align: center; }
                .metric-value { font-size: 2em; font-weight: bold; color: #1779ba; margin: 10px 0; }
                .metric-label { color: #666; font-size: 0.9em; }
                .table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                .table th, .table td { border: 1px solid #ddd; padding: 12px; text-align: left; }
                .table th { background: #f8f9fa; font-weight: bold; }
                .table tr:nth-child(even) { background: #f9f9f9; }
                .chart-container { margin: 20px 0; padding: 20px; border: 1px solid #ddd; border-radius: 8px; }
                .chart-title { font-weight: bold; margin-bottom: 10px; color: #1779ba; }
                .footer { margin-top: 40px; text-align: center; color: #666; font-size: 0.8em; border-top: 1px solid #ddd; padding-top: 20px; }
                .positive { color: #28a745; }
                .negative { color: #dc3545; }
                .warning { color: #ffc107; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>CRMAIze Analytics Report</h1>
                <p>Generated on {$date} at {$time}</p>
                <p>Comprehensive Business Intelligence Dashboard</p>
            </div>

            <div class='section'>
                <h2>Executive Summary</h2>
                <div class='metrics-grid'>
                    <div class='metric-card'>
                        <div class='metric-value'>{$analytics['customers']['total']}</div>
                        <div class='metric-label'>Total Customers</div>
                    </div>
                    <div class='metric-card'>
                        <div class='metric-value'>$" . number_format($analytics['revenue']['total'], 2) . "</div>
                        <div class='metric-label'>Total Revenue</div>
                    </div>
                    <div class='metric-card'>
                        <div class='metric-value'>" . number_format($analytics['performance']['conversion_rate'], 1) . "%</div>
                        <div class='metric-label'>Conversion Rate</div>
                    </div>
                    <div class='metric-card'>
                        <div class='metric-value'>" . number_format($analytics['churn']['avg_risk'] * 100, 1) . "%</div>
                        <div class='metric-label'>Avg Churn Risk</div>
                    </div>
                </div>
            </div>

            <div class='section'>
                <h2>Customer Analytics</h2>
                <div class='metrics-grid'>
                    <div class='metric-card'>
                        <div class='metric-value'>" . number_format($analytics['customers']['growth_rate'], 1) . "%</div>
                        <div class='metric-label'>Growth Rate (30 days)</div>
                    </div>
                    <div class='metric-card'>
                        <div class='metric-value'>$" . number_format($analytics['customers']['avg_order_value'], 2) . "</div>
                        <div class='metric-label'>Average Order Value</div>
                    </div>
                    <div class='metric-card'>
                        <div class='metric-value'>{$analytics['customers']['at_risk_count']}</div>
                        <div class='metric-label'>At-Risk Customers</div>
                    </div>
                    <div class='metric-card'>
                        <div class='metric-value'>" . number_format($analytics['performance']['customer_engagement'], 1) . "</div>
                        <div class='metric-label'>Engagement Score</div>
                    </div>
                </div>

                <div class='chart-container'>
                    <div class='chart-title'>Customer Growth Trend</div>
                    <table class='table'>
                        <tr>
                            <th>Period</th>
                            <th>New Customers</th>
                            <th>Growth</th>
                        </tr>
                        <tr>
                            <td>Previous 30 Days</td>
                            <td>{$analytics['customers']['previous_customers']}</td>
                            <td>-</td>
                        </tr>
                        <tr>
                            <td>Last 30 Days</td>
                            <td>{$analytics['customers']['recent_customers']}</td>
                            <td class='" . ($analytics['customers']['growth_rate'] >= 0 ? 'positive' : 'negative') . "'>
                                " . ($analytics['customers']['growth_rate'] >= 0 ? '+' : '') . number_format($analytics['customers']['growth_rate'], 1) . "%
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class='section'>
                <h2>Revenue Analysis</h2>
                <div class='chart-container'>
                    <div class='chart-title'>Revenue by Customer Segment</div>
                    <table class='table'>
                        <tr>
                            <th>Segment</th>
                            <th>Revenue</th>
                            <th>Percentage</th>
                        </tr>";

    foreach ($analytics['revenue']['by_segment'] as $segment => $revenue) {
      $percentage = $analytics['revenue']['total'] > 0 ? ($revenue / $analytics['revenue']['total']) * 100 : 0;
      $html .= "
                        <tr>
                            <td>" . ucfirst($segment) . "</td>
                            <td>$" . number_format($revenue, 2) . "</td>
                            <td>" . number_format($percentage, 1) . "%</td>
                        </tr>";
    }

    $html .= "
                    </table>
                </div>

                <div class='chart-container'>
                    <div class='chart-title'>Top 5 Customers by Revenue</div>
                    <table class='table'>
                        <tr>
                            <th>Customer</th>
                            <th>Email</th>
                            <th>Revenue</th>
                        </tr>";

    foreach (array_slice($analytics['revenue']['top_customers'], 0, 5) as $customer) {
      $html .= "
                        <tr>
                            <td>{$customer['name']}</td>
                            <td>{$customer['email']}</td>
                            <td>$" . number_format($customer['revenue'], 2) . "</td>
                        </tr>";
    }

    $html .= "
                    </table>
                </div>
            </div>

            <div class='section'>
                <h2>Churn Risk Analysis</h2>
                <div class='metrics-grid'>
                    <div class='metric-card'>
                        <div class='metric-value'>" . number_format($analytics['churn']['at_risk_percentage'], 1) . "%</div>
                        <div class='metric-label'>At-Risk Percentage</div>
                    </div>
                    <div class='metric-card'>
                        <div class='metric-value'>{$analytics['churn']['distribution']['low']}</div>
                        <div class='metric-label'>Low Risk Customers</div>
                    </div>
                    <div class='metric-card'>
                        <div class='metric-value'>{$analytics['churn']['distribution']['medium']}</div>
                        <div class='metric-label'>Medium Risk Customers</div>
                    </div>
                    <div class='metric-card'>
                        <div class='metric-value'>{$analytics['churn']['distribution']['high']}</div>
                        <div class='metric-label'>High Risk Customers</div>
                    </div>
                </div>

                <div class='chart-container'>
                    <div class='chart-title'>Churn Risk by Segment</div>
                    <table class='table'>
                        <tr>
                            <th>Segment</th>
                            <th>Average Churn Risk</th>
                            <th>Risk Level</th>
                        </tr>";

    foreach ($analytics['churn']['by_segment'] as $segment => $risk) {
      $riskLevel = $risk < 0.3 ? 'Low' : ($risk < 0.7 ? 'Medium' : 'High');
      $riskClass = $risk < 0.3 ? 'positive' : ($risk < 0.7 ? 'warning' : 'negative');
      $html .= "
                        <tr>
                            <td>" . ucfirst($segment) . "</td>
                            <td>" . number_format($risk * 100, 1) . "%</td>
                            <td class='{$riskClass}'>{$riskLevel}</td>
                        </tr>";
    }

    $html .= "
                    </table>
                </div>
            </div>

            <div class='section'>
                <h2>Campaign Performance</h2>
                <div class='metrics-grid'>
                    <div class='metric-card'>
                        <div class='metric-value'>{$analytics['campaigns']['total']}</div>
                        <div class='metric-label'>Total Campaigns</div>
                    </div>
                    <div class='metric-card'>
                        <div class='metric-value'>{$analytics['campaigns']['sent']}</div>
                        <div class='metric-label'>Sent Campaigns</div>
                    </div>
                    <div class='metric-card'>
                        <div class='metric-value'>" . number_format($analytics['performance']['campaign_success_rate'], 1) . "%</div>
                        <div class='metric-label'>Success Rate</div>
                    </div>
                    <div class='metric-card'>
                        <div class='metric-value'>" . number_format($analytics['campaigns']['avg_sent_per_campaign'], 0) . "</div>
                        <div class='metric-label'>Avg Sent per Campaign</div>
                    </div>
                </div>

                <div class='chart-container'>
                    <div class='chart-title'>Campaign Types Distribution</div>
                    <table class='table'>
                        <tr>
                            <th>Type</th>
                            <th>Count</th>
                            <th>Percentage</th>
                        </tr>";

    foreach ($analytics['campaigns']['types'] as $type => $count) {
      $percentage = $analytics['campaigns']['total'] > 0 ? ($count / $analytics['campaigns']['total']) * 100 : 0;
      $html .= "
                        <tr>
                            <td>" . ucfirst($type) . "</td>
                            <td>{$count}</td>
                            <td>" . number_format($percentage, 1) . "%</td>
                        </tr>";
    }

    $html .= "
                    </table>
                </div>
            </div>

            <div class='footer'>
                <p>This report was generated automatically by CRMAIze Analytics System</p>
                <p>For questions or support, please contact your system administrator</p>
            </div>
        </body>
        </html>";

    return $html;
  }

  /**
   * Generate customer report HTML
   */
  private function generateCustomerReportHTML(array $analytics): string
  {
    $date = date('F j, Y');

    $html = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>CRMAIze Customer Report</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; color: #333; }
                .header { text-align: center; border-bottom: 2px solid #1779ba; padding-bottom: 20px; margin-bottom: 30px; }
                .header h1 { color: #1779ba; margin: 0; }
                .section { margin-bottom: 30px; }
                .section h2 { color: #1779ba; border-bottom: 1px solid #ddd; padding-bottom: 10px; }
                .table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                .table th, .table td { border: 1px solid #ddd; padding: 12px; text-align: left; }
                .table th { background: #f8f9fa; font-weight: bold; }
                .table tr:nth-child(even) { background: #f9f9f9; }
                .footer { margin-top: 40px; text-align: center; color: #666; font-size: 0.8em; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>CRMAIze Customer Report</h1>
                <p>Generated on {$date}</p>
            </div>

            <div class='section'>
                <h2>Customer Segments</h2>
                <table class='table'>
                    <tr>
                        <th>Segment</th>
                        <th>Count</th>
                        <th>Percentage</th>
                        <th>Revenue</th>
                        <th>Avg Revenue</th>
                        <th>Avg Churn Risk</th>
                    </tr>";

    foreach ($analytics['segments'] as $segment => $data) {
      $html .= "
                    <tr>
                        <td>" . ucfirst($segment) . "</td>
                        <td>{$data['count']}</td>
                        <td>" . number_format($data['percentage'], 1) . "%</td>
                        <td>$" . number_format($data['revenue'], 2) . "</td>
                        <td>$" . number_format($data['avg_revenue'], 2) . "</td>
                        <td>" . number_format($data['avg_churn_risk'] * 100, 1) . "%</td>
                    </tr>";
    }

    $html .= "
                </table>
            </div>

            <div class='section'>
                <h2>At-Risk Customers</h2>
                <table class='table'>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Total Spent</th>
                        <th>Last Order</th>
                        <th>Churn Risk</th>
                    </tr>";

    foreach ($analytics['customers']['at_risk_customers'] as $customer) {
      $html .= "
                    <tr>
                        <td>{$customer['first_name']} {$customer['last_name']}</td>
                        <td>{$customer['email']}</td>
                        <td>$" . number_format($customer['total_spent'], 2) . "</td>
                        <td>{$customer['last_order_date']}</td>
                        <td>" . number_format($customer['churn_risk'] * 100, 1) . "%</td>
                    </tr>";
    }

    $html .= "
                </table>
            </div>

            <div class='footer'>
                <p>CRMAIze Customer Report - {$date}</p>
            </div>
        </body>
        </html>";

    return $html;
  }

  /**
   * Generate campaign report HTML
   */
  private function generateCampaignReportHTML(array $analytics): string
  {
    $date = date('F j, Y');

    $html = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>CRMAIze Campaign Report</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; color: #333; }
                .header { text-align: center; border-bottom: 2px solid #1779ba; padding-bottom: 20px; margin-bottom: 30px; }
                .header h1 { color: #1779ba; margin: 0; }
                .section { margin-bottom: 30px; }
                .section h2 { color: #1779ba; border-bottom: 1px solid #ddd; padding-bottom: 10px; }
                .table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                .table th, .table td { border: 1px solid #ddd; padding: 12px; text-align: left; }
                .table th { background: #f8f9fa; font-weight: bold; }
                .table tr:nth-child(even) { background: #f9f9f9; }
                .footer { margin-top: 40px; text-align: center; color: #666; font-size: 0.8em; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>CRMAIze Campaign Report</h1>
                <p>Generated on {$date}</p>
            </div>

            <div class='section'>
                <h2>Campaign Summary</h2>
                <table class='table'>
                    <tr>
                        <th>Metric</th>
                        <th>Value</th>
                    </tr>
                    <tr>
                        <td>Total Campaigns</td>
                        <td>{$analytics['campaigns']['total']}</td>
                    </tr>
                    <tr>
                        <td>Sent Campaigns</td>
                        <td>{$analytics['campaigns']['sent']}</td>
                    </tr>
                    <tr>
                        <td>Draft Campaigns</td>
                        <td>{$analytics['campaigns']['draft']}</td>
                    </tr>
                    <tr>
                        <td>Scheduled Campaigns</td>
                        <td>{$analytics['campaigns']['scheduled']}</td>
                    </tr>
                    <tr>
                        <td>Total Sent</td>
                        <td>{$analytics['campaigns']['total_sent']}</td>
                    </tr>
                    <tr>
                        <td>Success Rate</td>
                        <td>" . number_format($analytics['performance']['campaign_success_rate'], 1) . "%</td>
                    </tr>
                </table>
            </div>

            <div class='section'>
                <h2>Campaign Types</h2>
                <table class='table'>
                    <tr>
                        <th>Type</th>
                        <th>Count</th>
                        <th>Percentage</th>
                    </tr>";

    foreach ($analytics['campaigns']['types'] as $type => $count) {
      $percentage = $analytics['campaigns']['total'] > 0 ? ($count / $analytics['campaigns']['total']) * 100 : 0;
      $html .= "
                    <tr>
                        <td>" . ucfirst($type) . "</td>
                        <td>{$count}</td>
                        <td>" . number_format($percentage, 1) . "%</td>
                    </tr>";
    }

    $html .= "
                </table>
            </div>

            <div class='section'>
                <h2>Recent Campaigns</h2>
                <table class='table'>
                    <tr>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Sent Count</th>
                        <th>Created</th>
                    </tr>";

    foreach ($analytics['campaigns']['recent'] as $campaign) {
      $html .= "
                    <tr>
                        <td>{$campaign['name']}</td>
                        <td>" . ucfirst($campaign['type']) . "</td>
                        <td>" . ucfirst($campaign['status']) . "</td>
                        <td>{$campaign['sent_count']}</td>
                        <td>{$campaign['created_at']}</td>
                    </tr>";
    }

    $html .= "
                </table>
            </div>

            <div class='footer'>
                <p>CRMAIze Campaign Report - {$date}</p>
            </div>
        </body>
        </html>";

    return $html;
  }
}
