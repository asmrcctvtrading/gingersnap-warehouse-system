<!-- converted from INV-2026-BARTER-001-Simple.docx -->

INVESTIGATION REPORT
INV-2026-BARTER-001  |  Simple Language Version
"The Price That Was Changed to Zero"
| Report Number | INV-2026-BARTER-001 |
| --- | --- |
| Date | October 15, 2026 |
| Written By | IT Investigation Team |
| Item Affected | SKU 999529 — price changed from PHP 18.00 to PHP 0.0 (FREE!) |

# Section 1 — What Happened? (The Short Story)
Imagine a store that sells a small item for 18 pesos. One day, a computer made a mistake and changed the price tag to 0 pesos — meaning FREE! Nobody noticed for almost three days.

Here is what happened. On September 28, 2026, at 9:20 in the morning, a computer account called it01gingersnaps@gmail.com changed the price of an item from PHP 18.00 to PHP 0.0. It also erased some other information about the item at the same time.
About an hour and 40 minutes later, a person named Reniel Erick Dadulla sent that wrong price out to other computer systems — like telling all the other stores about the wrong price tag.
Nobody in IT noticed the mistake for 55 hours. That is more than two whole days!
On September 30, the stores started telling people something was wrong. The MPC team (the people in charge of prices) had to stop their own work and fix the price themselves, changing it back to PHP 18.00.
As of the last email we have, IT still had not explained why the price changed in the first place.
# Section 2 — Timeline (What Happened, Step by Step)
This is the story of what happened, in order — like a diary.

| When | What Happened | Proof? | Sure? |
| --- | --- | --- | --- |
| Sep 28, 9:20 AM | A computer account changed the price from PHP 18.00 to PHP 0.0 automatically | Evidence B | YES |
| Sep 28, 9:20–11:00 AM | Wrong price sitting in the computer, but not yet sent to stores. We do not know exactly what happened during this time. | — | GAP |
| Sep 28, 11:00 AM | Reniel Dadulla ran a sync job — the wrong 0.0 price was sent out to other computer systems | Evidence C | YES |
| Sep 28 – Sep 30 | Stores started seeing the wrong price. Exact time of first complaint is unknown. | — | GAP |
| Sep 30, 2:02 PM | MPC team emailed IT — stores are already complaining! | Evidence A | YES |
| Sep 30, 4:44 PM | MPC team said they will fix the price themselves. IT still has not explained what caused the mistake. | Evidence A | YES |

Total time the wrong price was out there: about 55 hours (more than 2 full days!)
# Section 3 — Why Did This Happen?
### 3.1 The Computer Did It Automatically
All the changes to the item happened at exactly the same second, by the same computer account. This was NOT a person clicking buttons slowly — it was a computer program doing it automatically and very fast.
When a sale or promotion ends, the correct thing to do is to put the old real price back (18.00). But the program put 0.0 instead. That is the mistake — like a robot that forgot what the original price was.
### 3.2 The Sync Job Made It Worse
The sync job (the one Reniel ran) did NOT cause the price to change. The price was already wrong before he ran it. What the sync job did was spread the wrong price to other systems — like handing out the wrong price tag to ALL the other stores at the same time.
### 3.3 A Broken Program (Bug)
The item's promotion number and collection name were also erased at the same time as the price change. This tells us a computer program was trying to "turn off" a promotion. But the program had a bug — like a mistake in a recipe — and it wrote 0.0 instead of putting the real price back.
### 3.4 No Permission Was Asked
Nobody asked the MPC team for permission before changing the price. There was no approval — like changing a test answer without asking the teacher first. The price was just changed without anyone checking.
# Section 4 — Who Was Affected?
| Who | How They Were Affected |
| --- | --- |
| MPC Team | Had to stop their own work to manually fix someone else's mistake. |
| Store Staff | Many stores saw the wrong price and raised concerns. Exact number of stores not specified. |
| Customers (Possible) | Could have been sold the item for FREE (PHP 0.0) instead of PHP 18.00. No confirmed cases in evidence. |
| Connected Computer System (ilcbcorp_2833) | Also received and stored the wrong price after the sync job ran. |
| Price Error | PHP 18.00 → PHP 0.0. That is a 100% error — the full price was gone! |
| How Long | At least 55 hours (over 2 days) |
# Section 5 — Who Was Involved?
### 5.1 The Computer Account: it01gingersnaps@gmail.com
This account made the price change. Based on the name, it looks like an IT computer account (it01 probably means "IT Account Number 1," and gingersnaps is the company name).
•  This account uses Gmail — a personal-style email. Company computer systems should use proper company email accounts, not Gmail.
•  The change was made by a program, not a person clicking buttons. But this account's name is on the change.
•  IT needs to explain: who controls this account? Was it a program or a person?
### 5.2 Reniel Erick Dadulla
Reniel ran the sync job that spread the wrong price to other systems. He most likely did NOT know the price was already wrong when he ran it. But because the sync ran without checking the data first, the mistake became a much bigger problem.
### 5.3 Missing Approval
Nobody from the MPC team approved the price change before it happened. There is no record of anyone asking for permission or filling out a change request form. This is like changing answers on a shared test without asking anyone.
# Section 6 — What Type of Problem Was This?
This problem had THREE causes that worked together like a chain — if any one of them had been fixed, the problem might not have happened:

•  Cause 1 — Broken Program: A computer program had a bug. It was supposed to put the real price back when a promotion ended, but it accidentally wrote 0.0 instead.
•  Cause 2 — No Approval: Nobody got permission from the MPC team before the price was changed. There was no approval step.
•  Cause 3 — Sync Without Checking: The sync job spread the wrong price to other systems without checking if the price made sense first.

This was NOT done by a hacker or a bad person from outside the company. It was an inside mistake caused by a broken program and a missing safety step.
# Section 7 — Questions IT Still Needs to Answer
IT needs to answer all of these questions. As of September 30, nobody knows the answers yet.

•  Question 1: Was the price change made by an automatic program, or did a person do it on purpose? If it was a program, which one? Why did it run on September 28?
•  Question 2: Who owns and is responsible for the it01gingersnaps@gmail.com account?
•  Question 3: Why did the program write 0.0 instead of the real price of PHP 18.00?
•  Question 4: Was there an approved change request form filled out? If not, why not?
•  Question 5: Did Reniel Dadulla know something was wrong with the data before he ran the sync job?
•  Question 6: How many items were in that sync job? Were any other items also given wrong prices?
•  Question 7: Why did no alarm or warning go off when the price became 0.0? Should there not be a safety check for that?
•  Question 8: Is the BarterMMS system supposed to touch the main price when a promotion ends? Has anyone written down the rules for how this should work?
# Section 8 — How Do We Stop This From Happening Again?
These are the things we should fix — think of them like new safety rules for the computer system.

| # | How Important | What to Fix (In Simple Words) |
| --- | --- | --- |
| R1 | VERY IMPORTANT | Lock the price field. Only accounts approved by the MPC team should be allowed to change the main price. Like a locked drawer — not everyone gets a key. |
| R2 | VERY IMPORTANT | Before any price is changed, the MPC team must say "yes" in writing. No price changes without approval — like asking the teacher before erasing someone else's answer. |
| R3 | VERY IMPORTANT | Set up an automatic alarm. If any item's price becomes 0.0, the computer should immediately send a warning to IT and MPC — like a smoke detector that goes off right away. |
| R4 | IMPORTANT | Before the sync job sends information out, it should check for strange prices like 0.0. If found, stop and ask someone to check first before continuing. |
| R5 | IMPORTANT | Stop using Gmail accounts for important computer system tasks. Replace them with proper company email accounts. |
| R6 | IMPORTANT | Fix the broken program so that when a promotion ends, it puts the correct original price back — not 0.0. |
| R7 | IMPORTANT | Make a rule: when a price complaint comes in, IT must respond within 2 hours and explain the cause within 24 hours. Like telling the teacher what happened before the school day ends. |
# Section 9 — A Note From the Investigator
This investigation only looked at the clues that were available. Nothing in any computer system was changed or touched during the investigation.
All the conclusions in this report came from three pieces of evidence:
•  Evidence A — An email conversation between MPC and IT
•  Evidence B — A screenshot of a change log showing who changed what and when
•  Evidence C — A screenshot of the job monitoring screen showing the sync job

When something could not be proven directly — just guessed based on the clues — the report said "inferred" so readers know it is not 100% certain.
This report does NOT say that anyone did something wrong on purpose. It only explains what happened based on the evidence. Decisions about what happens next — like whether anyone gets in trouble — are for the managers to decide, not the investigator.

─────────────────────────────────────────────────
INV-2026-BARTER-001  |  Simple Language Version  |  October 15, 2026