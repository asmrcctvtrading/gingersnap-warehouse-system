# Metrics Calculation Reference
## Warehouse Personnel Management System (WPMS)

---

## Overview

This document provides detailed mathematical formulas, calculations, and real-world examples for all metrics used in the WPMS Reporting Module. Understanding these metrics is essential for accurate performance interpretation.

---

## Core Metrics Formulas

### 1. Tasks Per Hour

**Formula:**
```
Tasks Per Hour = COUNT(completed_tasks) / (total_seconds_worked / 3600)
```

**Example Calculation:**

Scenario: Employee worked 8 hours and completed 40 picking tasks

```
Tasks Per Hour = 40 / (8 hours)
Tasks Per Hour = 5.0 tasks/hour
```

**Target**: 5.0 tasks/hour
**Why It Matters**: Measures individual productivity and identifies capacity planning needs

---

### 2. Average Task Duration

**Formula:**
```
Average Task Duration = SUM(task_duration_seconds) / COUNT(completed_tasks)
```

**Example Calculation:**

Scenario: 10 completed picking tasks with total duration of 6,998 seconds

```
Average Task Duration = 6,998 / 10
Average Task Duration = 699.8 seconds = 11.66 minutes
```

**Target**: <12 minutes
**Why It Matters**: Shows task complexity and identifies bottlenecks

---

### 3. Error Rate (%)

**Formula:**
```
Error Rate % = COUNT(tasks_with_errors) / COUNT(total_tasks) × 100
```

**Example Calculation:**

Scenario: Weekly picking data with 876 total tasks and 38 with errors

```
Error Rate = (38 / 876) × 100
Error Rate = 4.34%
```

**Error Breakdown:**
- Wrong Item: 18 (2.05%)
- Wrong Quantity: 12 (1.37%)
- Mislabeled: 5 (0.57%)
- Other: 3 (0.34%)

**Target**: <3.0%
**Why It Matters**: Measures accuracy and quality, affects customer satisfaction

---

### 4. Completion Rate (%)

**Formula:**
```
Completion Rate % = COUNT(completed) / COUNT(assigned) × 100
```

**Example Calculation:**

Scenario: 50 boxes assigned, 49 completed

```
Completion Rate = (49 / 50) × 100
Completion Rate = 98.0%
```

**Target**: >97.0%
**Why It Matters**: Shows if team keeps up with assignments and affects fulfillment dates

---

### 5. Quality Score (%)

**Formula:**
```
Quality Score % = COUNT(perfect_boxes) / COUNT(total_boxes) × 100
```

**Example Calculation:**

Scenario: 187 boxes packed, 185 passing QA inspection

```
Quality Score = (185 / 187) × 100
Quality Score = 98.93%
```

**Target**: >98.5%
**Why It Matters**: Reflects packing accuracy and impacts customer satisfaction

---

### 6. Productivity Score

**Formula:**
```
Productivity Score = (Completion Rate × 0.5) + ((100 - Error Rate) × 0.5)
```

**Example Calculation:**

Scenario: Employee with 99.2% completion rate and 1.8% error rate

```
Productivity Score = (99.2 × 0.5) + ((100 - 1.8) × 0.5)
Productivity Score = (49.6) + (49.1)
Productivity Score = 98.7
```

**Performance Scale:**
- 90-100: Excellent
- 80-89: Good
- 70-79: Average
- 60-69: Below Average
- <60: Poor

**Why It Matters**: Provides holistic performance view balancing speed and accuracy

---

## Inventory Metrics

### 7. Inventory Accuracy (%)

**Formula:**
```
Inventory Accuracy % = (verified_count / system_count) × 100
```

**Example Calculation:**

Scenario: System shows 2,345 units, physical count is 2,334

```
Inventory Accuracy = (2,334 / 2,345) × 100
Inventory Accuracy = 99.53%
```

**Target**: >99.0%
**Why It Matters**: Indicates data quality and affects order fulfillment accuracy

---

### 8. Stock Turnover Rate

**Formula:**
```
Stock Turnover = Units Sold / Average Units in Stock
```

**Example Calculation:**

Scenario: 
- Beginning inventory: 44,567 units
- Ending inventory: 45,267 units
- Units picked/sold: 103,456 units

```
Average Inventory = (44,567 + 45,267) / 2 = 44,917 units
Stock Turnover = 103,456 / 44,917
Stock Turnover = 2.30 times per month
```

**Annual Projection**: 2.30 × 12 = 27.6x per year

**Why It Matters**: Shows inventory efficiency and identifies slow-moving items

---

## Financial Metrics

### 9. Labor Cost Per Unit

**Formula:**
```
Labor Cost Per Unit = Total Labor Cost / Units Processed
```

**Example Calculation:**

Scenario:
- Total labor cost: ₱61,440
- Total units processed: 5,220 units

```
Labor Cost Per Unit = ₱61,440 / 5,220
Labor Cost Per Unit = ₱11.78 per unit
```

**Monthly Trend:**
- October: ₱12.44 per unit
- September: ₱13.12 per unit
- Improvement: 5.2%

**Why It Matters**: Shows operational efficiency and impacts profitability

---

## Metric Limitations

### Important Caveats

1. **Context Dependency**: Metrics must be viewed with context
2. **Data Quality Issues**: Incomplete time tracking reduces accuracy
3. **External Factors**: Product mix and equipment affect performance
4. **Human Factors**: Learning curve and fatigue impact results

---

## Data Freshness

### Update Frequency

| Metric | Update Frequency | Latency |
|--------|-----------------|---------|
| Tasks Per Hour | Every 5 min | <5 min |
| Average Duration | Every 5 min | <10 min |
| Error Rate | Every 15 min | <15 min |
| Quality Score | Every 30 min | <30 min |
| Inventory Accuracy | Daily | 24 hours |
| Stock Turnover | Weekly | 7 days |

---

## Metrics Reference Table

### All Metrics Summary

| Metric | Unit | Target | Update |
|--------|------|--------|--------|
| Tasks Per Hour | tasks/hr | 5.0+ | 5 min |
| Avg Duration | minutes | <12 | 5 min |
| Error Rate | % | <3.0% | 15 min |
| Completion Rate | % | >97% | 5 min |
| Quality Score | % | >98.5% | 30 min |
| Rework Rate | % | <1.0% | 30 min |
| Productivity Score | points | >95 | Daily |
| Inventory Accuracy | % | >99% | Daily |
| Stock Turnover | x/month | 2.0+ | Weekly |
| Labor Cost/Unit | ₱/unit | <12 | Daily |

---

**Last Updated**: October 24, 2026
**Version**: 1.0
