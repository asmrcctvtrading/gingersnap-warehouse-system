<!-- converted from INV-2026-BARTER-001.docx -->

FORMAL INVESTIGATION REPORT
BarterMMS Advanced Promotions V2 — Price Reversion Incident
SKU 999529 | BASE_PRICE Change from 18.00 to 0.0
| Report Reference | INV-2026-BARTER-001 |
| --- | --- |
| Investigation Type | Read-Only, Evidence-Based |
| Prepared By | Senior IT Investigation Lead |
| System Owner | Information Technology Department |
| Date of Report | October 15, 2026 |
| Evidence Reviewed | Evidence A (capture.jpg), Evidence B (image 6), Evidence C (image 7) |

# 1. Executive Summary
On September 28, 2026 at approximately 9:20 AM, a critical pricing incident occurred in the BarterMMS system affecting SKU 999529. The item's BASE_PRICE was changed from PHP 18.00 to PHP 0.0 via a REST API call attributed to the account it01gingersnaps@gmail.com. Concurrent with this price change, two promotional configuration fields — COLLECTION NAME and Promo # — were cleared, and the COLOR field was also blanked.
Approximately one hour and forty minutes after these changes, a full synchronization export job (full_sync_export_ilcbcorp_2833) was executed by user Reniel Erick Dadulla, which propagated the erroneous 0.0 price to downstream or connected systems.
The incident went undetected at the IT level for approximately 55 hours. By the afternoon of September 30, 2026, multiple stores had begun raising concerns, prompting the MPC Department to escalate via email to the IT Manager. MPC subsequently decided to manually revert the price to its actual value. As of the escalation email (September 30, 4:44 PM), IT had not provided an explanation for the cause of the price reversion.
The incident represents a combined failure of change control, REST API access governance, and automated sync job oversight.
# 2. Incident Timeline
| Timestamp | Event | Source | Status |
| --- | --- | --- | --- |
| Sep 28, 2026 9:20 AM | COLLECTION NAME, Promo #, COLOR, and BASE_PRICE (18.00 → 0.0) changed via REST API by it01gingersnaps@gmail.com | Evidence B | Confirmed |
| Sep 28, 2026 9:20 AM – 11:00 AM | Gap period: price was 0.0 in BarterMMS but sync had not yet propagated | — | Timeline Gap |
| Sep 28, 2026 11:00:41 AM | Full sync export job (full_sync_export_ilcbcorp_2833) run by Reniel Erick Dadulla — Status: Done | Evidence C | Confirmed |
| Sep 28 – Sep 30, 2026 | Stores begin experiencing the pricing discrepancy; exact first complaint timestamp unconfirmed | — | Timeline Gap |
| Sep 30, 2026 2:02 PM | Mary Rose de Luis (MPC Dept.) sends initial email — stores already raising concerns | Evidence A | Confirmed |
| Sep 30, 2026 4:44 PM | MPC confirms manual revert to actual price; IT has still not explained root cause | Evidence A | Confirmed |

Total Duration of Pricing Exposure: Approximately 55 hours and 44 minutes (minimum).
# 3. Root Cause Analysis
### 3.1 Nature of the 9:20 AM Changes (Evidence B)
All four field changes to SKU 999529 occurred at exactly 9:20 AM on September 28, 2026, all attributed to the same actor (it01gingersnaps@gmail.com) and the same method (REST API). The simultaneous timing and breadth of fields affected indicate this was a single API call or a tightly batched series, not a manual field-by-field entry.
The clearing of COLLECTION NAME and Promo # is consistent with a promotion deactivation or reset operation. However, a promotion reset does not inherently justify a price change from 18.00 to 0.0. A correctly configured promotional pricing workflow would restore the base price to its original value, not zero it out.
•  Assessment: The price change was caused by a direct API write on the BASE_PRICE field. The API call was the originating event.
### 3.2 Relationship Between the 9:20 AM API Changes and the 11:00 AM Sync Job
The full_sync_export_ilcbcorp_2833 job executed approximately 1 hour and 40 minutes after the price field was already overwritten to 0.0. The sync job did NOT cause the price change — the price was already 0.0 in BarterMMS before the sync ran. The sync job propagated the erroneous price to downstream connected systems, widening the impact.
•  Critical Finding: The root cause is the 9:20 AM REST API write. The 11:00 AM sync job is a propagation mechanism, not the origin.
### 3.3 Promotion Configuration Error
The simultaneous clearing of Promo # and COLLECTION NAME alongside BASE_PRICE is consistent with a promotion lifecycle management script executed incorrectly or misconfigured — writing 0.0 as the "reset" price rather than fetching the actual base price from a master price list.
### 3.4 Authorization and Process Gap
There is no evidence that the change was authorized through a formal change request or communicated to MPC prior to execution. MPC's email explicitly states IT had not provided an explanation — indicating IT itself may not have been aware of who initiated the API call. This suggests the REST API account can execute price field changes without a prior approval workflow.
# 4. Impact Assessment
| Area | Detail |
| --- | --- |
| MPC Department | Directly affected. Resources diverted to incident response and manual reversion. |
| Store Operations | Multiple stores raised concerns (plural confirmed in Evidence A). Exact count undocumented. |
| Customers (Potential) | If POS consumed BarterMMS price in real time, items may have been offered/charged at PHP 0.0. No confirmed transactions in evidence. |
| Connected Entity (ilcbcorp_2833) | Received the 0.0 price after the 11:00 AM sync. All reports/operations post-sync carried erroneous price. |
| Price Discrepancy | PHP 18.00 → PHP 0.0 (100% underpricing) |
| Exposure Duration | ~55 hours and 44 minutes (Sep 28, 9:20 AM to Sep 30, 4:44 PM minimum) |
# 5. Accountability Findings
### 5.1 Account: it01gingersnaps@gmail.com
This account is directly responsible for the four field changes recorded in the audit log. Based on naming convention:
•  The prefix "it01" is consistent with an IT department-issued operator account (possibly part of a numbered series: it01, it02…).
•  The domain "gingersnaps" is consistent with the Gingersnaps organizational namespace.
•  Use of a Gmail domain (@gmail.com) rather than a corporate domain is a governance concern. Service accounts should not use consumer Gmail addresses.
•  The REST API access method indicates this account is configured for programmatic operations.
Assessment: Most likely an IT operator or service account. Whether an automated script or a human-initiated API call cannot be determined from evidence alone — IT must clarify this.
### 5.2 User: Reniel Erick Dadulla
Reniel Erick Dadulla initiated the full_sync_export_ilcbcorp_2833 job at 11:00:41 AM. The sync job is assessed as a propagation event, not the originating cause. Whether Dadulla was aware the upstream data was corrupted before triggering the sync is unknown.
Assessment: Role is assessed as coincidental in intent, but consequential in impact. Running a full sync without pre-flight validation widened the blast radius.
### 5.3 Authorization Gap
No evidence of a formal change request, approval chain, or MPC notification prior to the 9:20 AM API operation exists in the provided documentation. This is a significant authorization gap.
# 6. Classification
Primary Classification: Combined / Multiple Causes
•  1. Promotion Configuration Error — Script wrote 0.0 instead of restoring the original base price.
•  2. Process and Authorization Gap — No evidence of MPC approval or change request prior to execution.
•  3. Automated Sync Propagation Risk — Full sync ran without a validation gate, amplifying exposure.

Not classified as: Unauthorized API Change in the intrusion sense — no evidence of external compromise. The change appears made by a known IT account within its technical permissions but outside its authorized operational scope.
# 7. Open Questions for IT
The following must be answered by IT per MPC's formal escalation (Evidence A):
•  1. What triggered the 9:20 AM API call? Automated script or manual? If automated, which script and what triggered it?
•  2. Who has operational control over it01gingersnaps@gmail.com? Is this shared or assigned to a specific individual?
•  3. Why was BASE_PRICE set to 0.0 and not restored to PHP 18.00? Why did the process not reference the master price list?
•  4. Was there an authorized change request for this operation? If yes, provide the reference. If not, why was it executed without MPC authorization?
•  5. Was Reniel Erick Dadulla aware SKU 999529's price was 0.0 before triggering the full sync? Was any pre-sync validation performed?
•  6. What is the scope of the full_sync_export_ilcbcorp_2833 job? Were other SKUs also affected?
•  7. Why was the incident not detected by IT between Sep 28, 9:20 AM and the store escalation on Sep 30? Is there a monitoring mechanism for BASE_PRICE changes?
•  8. Does BarterMMS Advanced Promotions V2 configuration intentionally write to BASE_PRICE when promotions are cleared? Is this behavior documented?
# 8. Recommendations
| # | Priority | Recommendation |
| --- | --- | --- |
| R1 | HIGH | Restrict REST API write access to BASE_PRICE. Require MPC-level credentials or dual-approval token for any API write to price-sensitive fields. |
| R2 | HIGH | Establish a change management protocol: any modification to BASE_PRICE or promotional fields must require written MPC approval and IT acknowledgment with timestamp. |
| R3 | HIGH | Implement real-time audit alerting. Any BASE_PRICE modification via REST API (especially to 0.0 or a value changed by more than a defined threshold) must trigger an immediate alert to IT Manager and MPC lead. |
| R4 | MEDIUM | Add pre-sync validation gates to full sync export jobs. Flag anomalous values (BASE_PRICE = 0.0, or changed by >threshold in last 24 hours). Pause and require manual confirmation before proceeding. |
| R5 | MEDIUM | Audit and harden service account governance. Migrate it01gingersnaps@gmail.com and similar accounts to organizational domains. Store credentials in a secure secrets management system. |
| R6 | MEDIUM | Review and correct the promotion lifecycle script. Verify that promotional reset logic does not overwrite BASE_PRICE with 0.0. The script must fetch the master price before writing, or avoid writing BASE_PRICE during promotion deactivation entirely. |
| R7 | MEDIUM | Define an incident escalation SLA: acknowledgment within 2 hours of report, root cause response within 24 hours. A 55-hour gap before formal escalation is unacceptable for a pricing incident. |
# 9. Investigator's Statement
This investigation was conducted in a strictly read-only, evidence-based manner. No changes were made to any system, database, or file during the course of this investigation. All findings, conclusions, and recommendations contained in this report are derived exclusively from the three evidence items presented:
•  Evidence A — Email thread (capture.jpg): MPC Department escalation email chain
•  Evidence B — BarterMMS audit log screenshot (image 6): Field change records for SKU 999529
•  Evidence C — Job Monitoring screenshot (image 7): full_sync_export_ilcbcorp_2833 job entry

Where conclusions are drawn beyond direct evidence, they are labeled as inferred and based on logical deduction from documented facts. This report does not make findings of personal misconduct. Accountability findings identify accounts and users associated with specific system actions as recorded in audit logs; determination of disciplinary or corrective action, if any, remains the responsibility of management.

This report is intended for submission to department heads and relevant stakeholders as a factual basis for follow-up action by IT management and the MPC Department.

─────────────────────────────────────────────────
INV-2026-BARTER-001  |  BarterMMS Advanced Promotions V2 — Price Reversion Incident
Prepared: October 15, 2026  |  Information Technology Department