// Web Worker for background customer analysis
self.onmessage = function (e) {
  const { type, data } = e.data;

  switch (type) {
    case 'ANALYZE_CUSTOMERS':
      analyzeCustomers(data);
      break;
    case 'SEGMENT_CUSTOMERS':
      segmentCustomers(data);
      break;
    case 'CALCULATE_CHURN_RISK':
      calculateChurnRisk(data);
      break;
    default:
      self.postMessage({ type: 'ERROR', error: 'Unknown operation type' });
  }
};

function analyzeCustomers(customers) {
  try {
    const analysis = {
      totalCustomers: customers.length,
      totalRevenue: customers.reduce((sum, c) => sum + (c.total_spent || 0), 0),
      avgOrderValue: 0,
      churnRate: 0,
      segments: {},
      riskDistribution: { low: 0, medium: 0, high: 0 },
    };

    // Calculate averages and distributions
    if (customers.length > 0) {
      analysis.avgOrderValue = analysis.totalRevenue / customers.length;

      let totalRisk = 0;
      customers.forEach((customer) => {
        const risk = calculateCustomerChurnRisk(customer);
        totalRisk += risk;

        if (risk < 0.3) analysis.riskDistribution.low++;
        else if (risk < 0.7) analysis.riskDistribution.medium++;
        else analysis.riskDistribution.high++;
      });

      analysis.churnRate = totalRisk / customers.length;
    }

    // Segment customers
    const segments = segmentCustomers(customers);
    analysis.segments = segments;

    self.postMessage({
      type: 'ANALYSIS_COMPLETE',
      data: analysis,
    });
  } catch (error) {
    self.postMessage({
      type: 'ERROR',
      error: error.message,
    });
  }
}

function segmentCustomers(customers) {
  const segments = {
    high_value: [],
    at_risk: [],
    loyal: [],
    new: [],
    inactive: [],
  };

  customers.forEach((customer) => {
    const segment = determineSegment(customer);
    segments[segment].push(customer);
  });

  // Convert to counts for easier processing
  const segmentCounts = {};
  Object.keys(segments).forEach((segment) => {
    segmentCounts[segment] = segments[segment].length;
  });

  self.postMessage({
    type: 'SEGMENTATION_COMPLETE',
    data: { segments, segmentCounts },
  });
}

function calculateChurnRisk(customer) {
  const risk = calculateCustomerChurnRisk(customer);

  self.postMessage({
    type: 'CHURN_RISK_COMPLETE',
    data: { customerId: customer.id, risk },
  });
}

function determineSegment(customer) {
  const totalSpent = customer.total_spent || 0;
  const orderCount = customer.order_count || 0;
  const lastOrderDate = customer.last_order_date;
  const daysSinceLastOrder = lastOrderDate
    ? (Date.now() - new Date(lastOrderDate).getTime()) / (1000 * 60 * 60 * 24)
    : 999;

  // High value customers
  if (totalSpent > 1000 && orderCount > 5) {
    return 'high_value';
  }

  // At risk customers
  if (daysSinceLastOrder > 90 && totalSpent > 100) {
    return 'at_risk';
  }

  // Loyal customers
  if (orderCount > 3 && daysSinceLastOrder < 30) {
    return 'loyal';
  }

  // New customers
  if (orderCount <= 1 && daysSinceLastOrder < 60) {
    return 'new';
  }

  // Inactive customers
  return 'inactive';
}

function calculateCustomerChurnRisk(customer) {
  const totalSpent = customer.total_spent || 0;
  const orderCount = customer.order_count || 0;
  const lastOrderDate = customer.last_order_date;
  const daysSinceLastOrder = lastOrderDate
    ? (Date.now() - new Date(lastOrderDate).getTime()) / (1000 * 60 * 60 * 24)
    : 999;

  let risk = 0.0;

  // Time since last order (higher weight)
  if (daysSinceLastOrder > 180) {
    risk += 0.4;
  } else if (daysSinceLastOrder > 90) {
    risk += 0.3;
  } else if (daysSinceLastOrder > 30) {
    risk += 0.1;
  }

  // Order frequency
  if (orderCount <= 1) {
    risk += 0.2;
  } else if (orderCount <= 3) {
    risk += 0.1;
  }

  // Spending pattern
  if (totalSpent < 50) {
    risk += 0.2;
  } else if (totalSpent < 200) {
    risk += 0.1;
  }

  return Math.min(1.0, risk);
}
