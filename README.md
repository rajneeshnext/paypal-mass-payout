# paypal-mass-payout
Making mass payments to paypal users

I am looking to develop an app that can send rewards to my users directly using paypal.
So essentially I have a list of users in a form of CSV with their paypal email address.
I need an app that can prove the concept can work and read the list from the CSV file, check each email, if they actually exist on paypal, send them the designated amount in the designated currency and add a description message.

and in the end display a report of what has been sent, total money spent & balance left..

=============

Here are my inputs:-

# I can see that the payout API does permit sending payments to any random email, we can test this with existing or non-existing email in a test environment.
# The PayPal sandbox has tools to test this feature, so testing this feature before pushing live won't be an issue.

===
Project involves

#1 CSV upload to DB
#2 Payout in the loop, with the manual call to a function.
#3 Set up an automated script(CRON) in the background to process payout automatically.
