# Payment gateway MTN Africa testing #

This plugin can be tested using PHPUnit and Behat. However, a lot of the standard tests are skipped.

It is possible to test with real data, just add some environment variables before you do some tests:

    env login=13300000-aaaa-bbbb-cccc-000000000f93
    env secret=61e00000-dddd-eeee-ffff-000000000157
    env secret1=35600000-gggg-hhhh-iiii-000000000823
    
    moodle-plugin-ci phpunit --coverage-text --coverage-clover payment/gateway/mtnafrica
    moodle-plugin-ci behat --coverage-text --coverage-clover payment/gateway/mtnafrica

You can also use repository secrets in GitHub actions (when using a private repository):

    gh secret set login --body "13300000-aaaa-bbbb-cccc-000000000f93"
    gh secret set secret --body "61e00000-dddd-eeee-ffff-000000000157"
    gh secret set secret1 --body "35600000-gggg-hhhh-iiii-000000000823"

Of course, you will need to provide your own login and secrets.
