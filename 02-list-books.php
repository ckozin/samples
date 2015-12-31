<?php
mysql_select_db($database_config, $config);
$term = mysql_real_escape_string($_GET["term"]);
$pagenum = mysql_real_escape_string($_GET["pagenum"]);
$action = mysql_real_escape_string($_GET["action"]);
if(isset($action)) {
    // Verify type of request 
    switch($action) {
        // Adding a book to the list
        case "add":
            // Get order id from cookie
            $order_id = $_COOKIE["order_id"];
            // Associate the ISBN13 identifier into the id for the cart table
            $id_item = $_GET["item"];
            // If we are adding an item follow this step
            $sameitemCount = mysql_query("SELECT COUNT(*) AS NR FROM rel WHERE id_item = '$id_item' and id_cart = '$order_id'");
            $sameitem_nr = mysql_fetch_array($sameitemCount);
            // If the book has not already been requested add it to the cart
            if ($sameitem_nr["NR"] <=0){
                // Use mysql query to link the items inside the rel table
                mysql_query("INSERT into rel (id_cart, id_item) values ('$order_id', '$id_item')");
            }
            break;
        // Deleting an item 
        case "delete":
            // Get order id from cookie
            $order_id = $_COOKIE["order_id"];
            // Associate the ISBN13 identifier into the id for the cart table
            $id_item = $_GET["item"];
            // Delete the item from rel table
            mysql_query("DELETE FROM rel WHERE id_cart = '$order_id' and id_item = '$id_item' LIMIT 1");
            break;
    }
}
// Get the number of items in the cart
$itemCount = mysql_query("SELECT COUNT(*) AS NR FROM rel WHERE id_cart = '$order_id'");
$item_nr = mysql_fetch_array($itemCount);
// If there are no books in the cart, write out empty message
if ($item_nr["NR"] <=0){
    echo '<p>You have no books requested.</p>';
}
// Otherwise show a listing of all the books that have been requested.
else {
    //  Display the count of the books
    echo '<p>You have '. $item_nr["NR"]. ' book(s) requested.</p>';
    // Query the cart items and display them on page
    $cart_items = mysql_query("SELECT id_item FROM rel WHERE id_cart = '$order_id'");
    while($cart_item = mysql_fetch_array($cart_items)){
        $cart_item_id = $cart_item["id_item"];
        // Get the details of the books in the cart from the titles table
        $book_details = mysql_query("SELECT * FROM titles WHERE isbn13 = '$cart_item_id'");
        // Put each book into array
        $book_details_row = mysql_fetch_array($book_details);
        ?>
        <div class="results">
            <div class="bookcover">
                <p><img src="http://covers.openlibrary.org/b/isbn/<?php echo $book_details_row["isbn13"]?>-S.jpg" alt="<?php echo $book_details_row["title"];?> cover" /></p>
            </div>
            <div class="details">
                <p>
                    <span class="booktitle"><?php echo $book_details_row["title"]; ?></span><br />
                    ISBN: <?php echo $book_details_row["isbn13"]; ?><br />
                    <?php echo $book_details_row["publisher"]; ?><br />
                    <?php if($book_details_row["pub_year"]!=""){echo $book_details_row["pub_year"]; ?><br /><?php } ?>
                    <?php if($book_details_row["cost"]!=""){echo $book_details_row["cost"]; ?><br /><?php } ?>
    				<?php $book=$book_details_row["isbn13"]; 
					echo "<a href='{$_SERVER['PHP_SELF']}?action=delete&item=$book'>Delete book</a>"; ?>
                </p>
            </div>
            <br />
        </div>
		<?php
	    }
    }	 
    // Check for search term being set
    if(isset($term)) {
        echo "<p><a href='search.php?term=$term&pagenum=$pagenum'>Back to search results for $term</a></p>";	
    }
?>