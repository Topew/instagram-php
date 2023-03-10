<?php

set_time_limit(0);
date_default_timezone_set('UTC');

require __DIR__.'/vendor/autoload.php';

/////// CONFIG ///////
$username = '';
$password = '';
$debug = true;
$truncatedDebug = false;
//////////////////////

$ig = new \InstagramAPI\Instagram($debug, $truncatedDebug);

try {
    $ig->login($username, $password);
} catch (\Exception $e) {
    echo 'Something went wrong: '.$e->getMessage()."\n";
    exit(0);
}

try {
    // There are different clusters for the explored feed, the default one is named "explore_all:0",
    // there are others as well: Animals: 'hashtag_inspired:1', Style: 'hashtag_inspired:26',
    // Comics: 'hashtag_inspired:20', Travel: 'hashtag_inspired:28', Architecture: 'hashtag_inspired:18',
    // Beauty: 'hashtag_inspired:3', DIY: 'hashtag_inspired:21', Auto: 'hashtag_inspired:19',
    // Music: 'hashtag_inspired:11', Nature: 'hashtag_inspired:24', Decor: 'hashtag_inspired:5',
    // Dance: 'hashtag_inspired:22'.
    $feed = $ig->discover->getExploreFeed('explore_all:0', \InstagramAPI\Signatures::generateUUID());

    // Let's begin by looking at a beautiful debug output of what's available in
    // the response! This is very helpful for figuring out what a response has!
    $feed->printJson();

    // Now let's look at what properties are supported on the $feed object. This
    // works on ANY object from our library, and will show what functions and
    // properties are supported, as well as how to call the functions! :-)
    $feed->printPropertyDescriptions();

    // The getExploreFeed() has an "sectional_items" property, which we need. As we saw
    // above, we should get it via "getSectionalItems()". The property list above told us
    // that it will return an array of "Section" objects. Therefore it's an ARRAY!
    $sectionalItems = $feed->getSectionalItems();

    foreach ($sectionalItems as $sectionalItem) {
        // Discover feed has different sections with bigger layouts and smaller ones.
        // For this example we are only targeting the 'media_grid' which are the small
        // layout for medias.
        if ($sectionalItem->getLayoutType() === 'media_grid') {
            // Now we can get the layout content which is the property "layout_content"
            // by using the function "getLayoutContent".
            $layoutContent = $sectionalItem->getLayoutContent();
            // We now exit the loop since we don't need to iterate for this example.
            break;
        }
    }

    // Now we will get the media items that are inside of the media grid layout.
    if ($layoutContent->getFillItems() !== null) {
        $items = $layoutContent->getFillItems();
    } elseif ($layoutContent->getMedias() !== null) {
        $items = $layoutContent->getMedias();
    }

    // Let's get the media item from the first item of the explore-items array...!
    $firstItem = $items[0]->getMedia();

    // We can look at that item too, if we want to... Let's do it! Note that
    // when we list supported properties, it shows everything supported by an
    // "Item" object. But that DOESN'T mean that every property IS available!
    // That's why you should always check the JSON to be sure that data exists!
    $firstItem->printJson(); // Shows its actual JSON contents (available data).
    $firstItem->printPropertyDescriptions(); // List of supported properties.

    // Let's look specifically at its User object!
    $firstItem->getUser()->printJson();

    // Okay, so the username of the person who posted the media is easy... And
    // as you can see, you can even chain multiple function calls in a row here
    // to get to the data. However, be aware that sometimes Instagram responses
    // have NULL values, so chaining is sometimes risky. But not in this case,
    // since we know that "user" and its "username" are always available! :-)
    $firstItem_username = $firstItem->getUser()->getUsername();

    // Now let's get the "id" of the item too!
    $firstItem_mediaId = $firstItem->getId();

    // Finally, let's get the highest-quality image URL for the media item!
    $firstItem_imageUrl = $firstItem->getImageVersions2()->getCandidates()[0]->getUrl();

    // Output some statistics. Well done! :-)
    echo 'There are '.count($items)." items.\n";
    echo "The first item has media id: {$firstItem_mediaId}.\n";
    echo "The first item was uploaded by: {$firstItem_username}.\n";
    echo "The highest quality image URL is: {$firstItem_imageUrl}.\n";
} catch (\Exception $e) {
    echo 'Something went wrong: '.$e->getMessage()."\n";
}
