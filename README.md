## TYPO3 extension TweetNews: Automatic Tweets from EXT:news newsitems

### What is it?

A hook-type processor which tweets the title of news items to Twitter when news items are saved.

### What does it do?

Tweets news items and links to news items. Nothing else.

### How does it work?

By connecting to the Twitter REST API using the third party PHP library "CodeBird" (thanks a bunch to `mynetx` for that one!).

Currently uses Flux for quick access to triggers on record saving hooks - a future (non-beta) version will remove this dependency.

### Where does it work?

On TYPO3 versions from 4.5 to and including 6.0

### How to install and use

1. Log in on Twitter's Developer site using the Twitter account you wish to use for auto tweeting. Address: https://dev.twitter.com/
2. Create a new Twitter application - name it, enter URLs etc.
3. Allow the application read AND write privileges to your feed.
4. Press the "Create Access Token" button.
5. Enter the values generated in `Consumer key`, `Consumer secret`, `Access token` and `Access token secret` in the corresponding
   TypoScript values (either use the included static TS file as a skeleton for your configuration or include it and override the
   token/secret settings along with any customisations you require).
6. Clear all caches.
7. Whenever you save a `tx_news_domain_model_news` record, a tweet is posted on Twitter under the account used to create the app.

### What must be true before tweets are allowed to be sent

1. The news item's date must be before the current timestamp. You can exploit this to prevent tweeting of news items which are
   otherwise visible on the site (simply set the date a few minutes into the future and no tweet is made).
2. The news item must be VISIBLE and NOT DELETED. Naturally so. You can use the "allow preview of hidden records" feature in
   EXT:news to allow previewing without tweeting (but note that only the title is used when tweeting).
3. The news item's title in its truncated version must not exist in the last N number of posts in the account's home timeline.
   This check is implemented to allow you to re-save the news record after it has been tweeted; only if you change the title will
   it trigger a new tweet, effectively preventing duplicates. However, this can result in some confusion with very low maximum
   title lengths and news posts which are very similarly named - when saving the news record you will see a message indicating
   when the news item was previously tweeted, you can use this information to track down the cause of unexpected tweet supression.
4. The news item must have an UID - in other words: it must have been saved at least once before tweets will trigger.

### Pitfalls

1. Make sure you set `plugin.tx_news.settings.defaultDetailPid` in a template inherited to the sysfolder or page in which you
   store your EXT:news records. If you need multiple target pids, use multiple storage folders and override TypoScript as needed.
2. Tweeting only happens when the record has been saved at least once prior. Usually this should not be a problem if you preview
   your news items but some, like me, do one-shot updates from time to time. In this case simply save the news item twice.

## Have fun, enjoy!