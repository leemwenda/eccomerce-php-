<?php
$pageTitle = 'Product';
require_once '../php/config/database.php';
require_once '../php/config/helpers.php';

$slug = clean($_GET['slug'] ?? '');
if (!$slug) { header('Location: shop.php'); exit; }

$stmt = $pdo->prepare("SELECT p.*, c.name AS category_name, c.slug AS category_slug FROM products p LEFT JOIN categories c ON p.category_id=c.id WHERE p.slug=?");
$stmt->execute([$slug]);
$product = $stmt->fetch();
if (!$product) { header('Location: shop.php'); exit; }

$pageTitle   = $product['name'];
$price       = !empty($product['sale_price']) ? $product['sale_price'] : $product['price'];
$hasDiscount = !empty($product['sale_price']);
$discount    = $hasDiscount ? round((1 - $product['sale_price'] / $product['price']) * 100) : 0;

// Gallery images (product_images table or fallback)
$galleryStmt = $pdo->prepare("SELECT image FROM product_images WHERE product_id=? ORDER BY sort_order ASC");
try {
    $galleryStmt->execute([$product['id']]);
    $galleryImages = $galleryStmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) { $galleryImages = []; }
// Always include main image first
$mainImg = !empty($product['image']) ? $product['image'] : 'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?w=800&q=80';
array_unshift($galleryImages, $mainImg);
$galleryImages = array_unique($galleryImages);

// Reviews
try {
    $reviewStmt = $pdo->prepare("SELECT * FROM reviews WHERE product_id=? AND approved=1 ORDER BY created_at DESC");
    $reviewStmt->execute([$product['id']]);
    $reviews = $reviewStmt->fetchAll();
    $avgRating = count($reviews) > 0 ? array_sum(array_column($reviews, 'rating')) / count($reviews) : 0;
} catch (Exception $e) { $reviews = []; $avgRating = 0; }

// Handle review submit
$reviewMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review_submit'])) {
    $rName    = trim($_POST['reviewer_name'] ?? '');
    $rRating  = (int)($_POST['rating'] ?? 0);
    $rComment = trim($_POST['comment'] ?? '');
    if ($rName && $rRating >= 1 && $rRating <= 5 && $rComment) {
        try {
            $userId = $_SESSION['user_id'] ?? null;
            $pdo->prepare("INSERT INTO reviews (product_id, user_id, name, rating, comment) VALUES (?,?,?,?,?)")
                ->execute([$product['id'], $userId, $rName, $rRating, $rComment]);
            $reviewMsg = 'success';
        } catch (Exception $e) { $reviewMsg = 'error'; }
    } else { $reviewMsg = 'invalid'; }
}

// Related products
$related = [];
if ($product['category_id']) {
    $stmt = $pdo->prepare("SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id=c.id WHERE p.category_id=? AND p.id!=? ORDER BY RAND() LIMIT 4");
    $stmt->execute([$product['category_id'], $product['id']]);
    $related = $stmt->fetchAll();
}

// Wishlist check
$sessionId  = getCartSessionId();
try {
    $wlStmt = $pdo->prepare("SELECT id FROM wishlist WHERE session_id=? AND product_id=?");
    $wlStmt->execute([$sessionId, $product['id']]);
    $inWishlist = (bool)$wlStmt->fetch();
} catch (Exception $e) { $inWishlist = false; }

require_once '../php/includes/header.php';
?>

<!-- BREADCRUMB -->
<section style="background:var(--bg-alt);border-bottom:1px solid var(--border);padding:1rem 0;">
    <div class="container">
        <p style="font-size:0.875rem;color:var(--text-muted);display:flex;align-items:center;gap:0.5rem;flex-wrap:wrap;">
            <a href="../index.php" style="color:var(--text-muted);"><i class="fa-solid fa-house"></i> Home</a>
            <i class="fa-solid fa-chevron-right" style="font-size:0.6rem;"></i>
            <a href="shop.php" style="color:var(--text-muted);">Shop</a>
            <?php if ($product['category_name']): ?>
            <i class="fa-solid fa-chevron-right" style="font-size:0.6rem;"></i>
            <a href="shop.php?category=<?php echo $product['category_id']; ?>" style="color:var(--text-muted);"><?php echo clean($product['category_name']); ?></a>
            <?php endif; ?>
            <i class="fa-solid fa-chevron-right" style="font-size:0.6rem;"></i>
            <span><?php echo clean($product['name']); ?></span>
        </p>
    </div>
</section>

<!-- PRODUCT DETAIL -->
<section class="section" style="padding-bottom:3rem;">
    <div class="container">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:4rem;align-items:start;">

            <!-- IMAGE GALLERY -->
            <div>
                <!-- Main Image -->
                <div style="position:relative;border-radius:var(--radius-lg);overflow:hidden;background:var(--bg-alt);border:1px solid var(--border);margin-bottom:1rem;">
                    <img id="mainProductImg"
                         src="<?php echo clean($galleryImages[0]); ?>"
                         alt="<?php echo clean($product['name']); ?>"
                         style="width:100%;height:520px;object-fit:cover;display:block;transition:opacity 0.3s;">
                    <?php if ($hasDiscount): ?>
                        <div style="position:absolute;top:1rem;left:1rem;background:var(--danger);color:white;font-size:0.85rem;font-weight:700;padding:0.4rem 0.9rem;border-radius:var(--radius-sm);">-<?php echo $discount; ?>% OFF</div>
                    <?php endif; ?>
                    <?php if ($product['stock'] <= 0): ?>
                        <div style="position:absolute;inset:0;background:rgba(0,0,0,0.5);display:flex;align-items:center;justify-content:center;">
                            <span style="background:white;color:var(--text);font-weight:700;padding:0.75rem 2rem;border-radius:var(--radius);">OUT OF STOCK</span>
                        </div>
                    <?php endif; ?>
                    <!-- Nav arrows (only if >1 image) -->
                    <?php if (count($galleryImages) > 1): ?>
                    <button onclick="changeMainImg(-1)" class="gallery-nav" style="left:0.75rem;"><i class="fa-solid fa-chevron-left"></i></button>
                    <button onclick="changeMainImg(1)"  class="gallery-nav" style="right:0.75rem;"><i class="fa-solid fa-chevron-right"></i></button>
                    <?php endif; ?>
                </div>

                <!-- Thumbnails -->
                <?php if (count($galleryImages) > 1): ?>
                <div style="display:flex;gap:0.75rem;flex-wrap:wrap;">
                    <?php foreach ($galleryImages as $i => $img): ?>
                    <div class="thumb-item <?php echo $i===0?'active':''; ?>"
                         onclick="setMainImg(<?php echo $i; ?>)"
                         data-src="<?php echo clean($img); ?>"
                         style="width:72px;height:72px;border-radius:var(--radius);overflow:hidden;cursor:pointer;border:2px solid <?php echo $i===0?'var(--primary)':'var(--border)'; ?>;transition:border-color 0.2s;flex-shrink:0;">
                        <img src="<?php echo clean($img); ?>" style="width:100%;height:100%;object-fit:cover;" loading="lazy">
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- PRODUCT INFO -->
            <div>
                <a href="shop.php?category=<?php echo $product['category_id']; ?>"
                   style="display:inline-block;font-size:0.78rem;font-weight:600;text-transform:uppercase;letter-spacing:0.12em;color:var(--primary);margin-bottom:0.75rem;">
                    <i class="fa-solid fa-tag" style="margin-right:0.35rem;"></i><?php echo clean($product['category_name'] ?? 'Uncategorized'); ?>
                </a>

                <h1 style="font-family:var(--font-display);font-size:2.25rem;font-weight:600;line-height:1.2;margin-bottom:1rem;"><?php echo clean($product['name']); ?></h1>

                <!-- Rating stars -->
                <?php if (count($reviews) > 0): ?>
                <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:1.25rem;">
                    <div style="display:flex;gap:2px;">
                        <?php for ($i=1;$i<=5;$i++): ?>
                        <i class="fa-<?php echo $i<=$avgRating?'solid':'regular'; ?> fa-star" style="color:#F59E0B;font-size:1rem;"></i>
                        <?php endfor; ?>
                    </div>
                    <span style="font-size:0.88rem;color:var(--text-muted);"><?php echo number_format($avgRating,1); ?> (<?php echo count($reviews); ?> review<?php echo count($reviews)!=1?'s':''; ?>)</span>
                    <a href="#reviews" style="font-size:0.85rem;color:var(--primary);">Read reviews</a>
                </div>
                <?php else: ?>
                <div style="margin-bottom:1.25rem;">
                    <span style="font-size:0.85rem;color:var(--text-muted);">No reviews yet. <a href="#reviews" style="color:var(--primary);">Be the first!</a></span>
                </div>
                <?php endif; ?>

                <!-- Price -->
                <div style="display:flex;align-items:center;gap:1rem;margin-bottom:1.5rem;">
                    <span style="font-size:2rem;font-weight:700;color:var(--primary);" data-price="<?php echo $price; ?>"><?php echo formatPrice($price); ?></span>
                    <?php if ($hasDiscount): ?>
                        <span style="font-size:1.1rem;text-decoration:line-through;color:var(--text-muted);" data-price="<?php echo $product['price']; ?>"><?php echo formatPrice($product['price']); ?></span>
                        <span style="background:#FEE2E2;color:var(--danger);font-size:0.8rem;font-weight:700;padding:0.25rem 0.65rem;border-radius:var(--radius-sm);">Save <?php echo $discount; ?>%</span>
                    <?php endif; ?>
                </div>

                <hr style="border:none;border-top:1px solid var(--border);margin-bottom:1.5rem;">

                <!-- Description -->
                <?php if (!empty($product['description'])): ?>
                    <p style="color:var(--text-muted);line-height:1.9;margin-bottom:1.75rem;font-size:0.95rem;"><?php echo clean($product['description']); ?></p>
                <?php endif; ?>

                <!-- Stock Status -->
                <div style="margin-bottom:1.75rem;">
                    <?php if ($product['stock'] > 10): ?>
                        <span style="display:inline-flex;align-items:center;gap:0.5rem;color:var(--accent);font-weight:600;font-size:0.9rem;background:#D1FAE5;padding:0.4rem 1rem;border-radius:2rem;">
                            <i class="fa-solid fa-circle-check"></i> In Stock (<?php echo $product['stock']; ?> available)
                        </span>
                    <?php elseif ($product['stock'] > 0): ?>
                        <span style="display:inline-flex;align-items:center;gap:0.5rem;color:#D97706;font-weight:700;font-size:0.9rem;background:#FEF3C7;padding:0.4rem 1rem;border-radius:2rem;animation:pulse 1.5s infinite;">
                            <i class="fa-solid fa-fire"></i> Only <?php echo $product['stock']; ?> left — Order soon!
                        </span>
                    <?php else: ?>
                        <span style="display:inline-flex;align-items:center;gap:0.5rem;color:var(--danger);font-weight:600;font-size:0.9rem;background:#FEE2E2;padding:0.4rem 1rem;border-radius:2rem;">
                            <i class="fa-solid fa-circle-xmark"></i> Out of Stock
                        </span>
                    <?php endif; ?>
                </div>

                <!-- Add to Cart + Wishlist -->
                <?php if ($product['stock'] > 0): ?>
                <div style="display:flex;gap:1rem;align-items:center;margin-bottom:1.25rem;">
                    <div style="display:flex;align-items:center;border:1.5px solid var(--border);border-radius:var(--radius);overflow:hidden;background:var(--bg-card);">
                        <button onclick="changeQty(-1,<?php echo $product['stock']; ?>)" style="width:42px;height:52px;background:none;border:none;font-size:1.1rem;color:var(--text-muted);cursor:pointer;display:flex;align-items:center;justify-content:center;" onmouseover="this.style.background='var(--bg-alt)'" onmouseout="this.style.background='none'"><i class="fa-solid fa-minus"></i></button>
                        <input type="number" id="quantity" value="1" min="1" max="<?php echo $product['stock']; ?>" style="width:56px;height:52px;text-align:center;border:none;border-left:1px solid var(--border);border-right:1px solid var(--border);font-size:1rem;font-weight:600;background:var(--bg-card);color:var(--text);font-family:var(--font-body);">
                        <button onclick="changeQty(1,<?php echo $product['stock']; ?>)"  style="width:42px;height:52px;background:none;border:none;font-size:1.1rem;color:var(--text-muted);cursor:pointer;display:flex;align-items:center;justify-content:center;" onmouseover="this.style.background='var(--bg-alt)'" onmouseout="this.style.background='none'"><i class="fa-solid fa-plus"></i></button>
                    </div>
                    <button class="btn btn-primary" data-add-to-cart="<?php echo $product['id']; ?>" style="flex:1;height:52px;font-size:1rem;justify-content:center;">
                        <i class="fa-solid fa-bag-shopping"></i> Add to Cart
                    </button>
                    <button id="wishlistBtn" onclick="toggleWishlist(this, <?php echo $product['id']; ?>)"
                            class="btn btn-outline <?php echo $inWishlist?'wishlisted':''; ?>"
                            style="height:52px;width:52px;padding:0;justify-content:center;<?php echo $inWishlist?'background:var(--danger);color:white;border-color:var(--danger);':''; ?>"
                            title="<?php echo $inWishlist?'Remove from wishlist':'Add to wishlist'; ?>">
                        <i class="fa-<?php echo $inWishlist?'solid':'regular'; ?> fa-heart"></i>
                    </button>
                </div>
                <a href="cart.php" class="btn btn-outline" style="width:100%;justify-content:center;margin-bottom:1.75rem;">
                    <i class="fa-solid fa-bolt"></i> View Cart & Checkout
                </a>
                <?php else: ?>
                <button class="btn" disabled style="width:100%;height:52px;background:var(--bg-alt);color:var(--text-muted);cursor:not-allowed;justify-content:center;margin-bottom:1.75rem;">
                    <i class="fa-solid fa-ban"></i> Out of Stock
                </button>
                <?php endif; ?>

                <!-- Benefits mini grid -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;padding:1.25rem;background:var(--bg-alt);border-radius:var(--radius-lg);border:1px solid var(--border);">
                    <?php
                    $benefits = [['fa-truck','Free shipping over KES 5,000'],['fa-rotate-left','30-day easy returns'],['fa-shield-halved','Secure checkout'],['fa-leaf','Sustainably sourced']];
                    foreach ($benefits as $b):
                    ?>
                    <div style="display:flex;align-items:center;gap:0.65rem;font-size:0.82rem;color:var(--text-muted);">
                        <i class="fa-solid <?php echo $b[0]; ?>" style="color:var(--primary);font-size:1rem;width:16px;text-align:center;"></i>
                        <?php echo $b[1]; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- REVIEWS SECTION -->
<section id="reviews" style="background:var(--bg-alt);border-top:1px solid var(--border);padding:4rem 0;">
    <div class="container">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:3rem;align-items:start;">

            <!-- Reviews List -->
            <div>
                <h2 style="font-family:var(--font-display);font-size:1.75rem;font-weight:600;margin-bottom:0.5rem;">
                    Customer Reviews
                    <?php if (count($reviews)>0): ?>
                        <span style="font-size:1rem;font-weight:400;color:var(--text-muted);font-family:var(--font-body);">(<?php echo count($reviews); ?>)</span>
                    <?php endif; ?>
                </h2>

                <?php if (count($reviews) > 0): ?>
                <!-- Rating summary -->
                <div style="display:flex;align-items:center;gap:1.5rem;margin-bottom:2rem;padding:1.25rem;background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);">
                    <div style="text-align:center;">
                        <div style="font-size:3rem;font-weight:700;color:var(--primary);line-height:1;"><?php echo number_format($avgRating,1); ?></div>
                        <div style="display:flex;gap:2px;justify-content:center;margin:0.25rem 0;">
                            <?php for ($i=1;$i<=5;$i++): ?><i class="fa-<?php echo $i<=$avgRating?'solid':'regular'; ?> fa-star" style="color:#F59E0B;font-size:0.9rem;"></i><?php endfor; ?>
                        </div>
                        <div style="font-size:0.78rem;color:var(--text-muted);"><?php echo count($reviews); ?> reviews</div>
                    </div>
                    <div style="flex:1;">
                        <?php
                        for ($star=5;$star>=1;$star--):
                            $cnt = count(array_filter($reviews, fn($r) => $r['rating']==$star));
                            $pct = count($reviews)>0 ? ($cnt/count($reviews))*100 : 0;
                        ?>
                        <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.35rem;">
                            <span style="font-size:0.75rem;width:12px;color:var(--text-muted);"><?php echo $star; ?></span>
                            <i class="fa-solid fa-star" style="color:#F59E0B;font-size:0.7rem;"></i>
                            <div style="flex:1;height:8px;background:var(--border);border-radius:4px;overflow:hidden;">
                                <div style="width:<?php echo $pct; ?>%;height:100%;background:#F59E0B;border-radius:4px;"></div>
                            </div>
                            <span style="font-size:0.75rem;width:16px;color:var(--text-muted);"><?php echo $cnt; ?></span>
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>

                <!-- Individual reviews -->
                <div style="display:flex;flex-direction:column;gap:1.25rem;max-height:450px;overflow-y:auto;padding-right:0.5rem;">
                    <?php foreach ($reviews as $rev): ?>
                    <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);padding:1.25rem;">
                        <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:0.75rem;">
                            <div>
                                <div style="display:flex;align-items:center;gap:0.65rem;margin-bottom:0.2rem;">
                                    <div style="width:34px;height:34px;background:var(--primary-light);border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;color:var(--primary);font-size:0.85rem;"><?php echo strtoupper(substr($rev['name'],0,1)); ?></div>
                                    <strong style="font-size:0.92rem;"><?php echo clean($rev['name']); ?></strong>
                                </div>
                                <div style="display:flex;gap:2px;">
                                    <?php for ($i=1;$i<=5;$i++): ?><i class="fa-<?php echo $i<=$rev['rating']?'solid':'regular'; ?> fa-star" style="color:#F59E0B;font-size:0.8rem;"></i><?php endfor; ?>
                                </div>
                            </div>
                            <span style="font-size:0.75rem;color:var(--text-muted);"><?php echo date('M j, Y', strtotime($rev['created_at'])); ?></span>
                        </div>
                        <p style="font-size:0.9rem;color:var(--text-muted);line-height:1.7;"><?php echo clean($rev['comment']); ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div style="text-align:center;padding:3rem;background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);">
                    <i class="fa-regular fa-star" style="font-size:2.5rem;color:var(--border);display:block;margin-bottom:1rem;"></i>
                    <p style="color:var(--text-muted);">No reviews yet. Be the first to share your thoughts!</p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Write Review Form -->
            <div>
                <h2 style="font-family:var(--font-display);font-size:1.75rem;font-weight:600;margin-bottom:1.5rem;">Write a Review</h2>

                <?php if ($reviewMsg === 'success'): ?>
                    <div style="background:#D1FAE5;color:#065F46;border:1px solid #6EE7B7;padding:1rem;border-radius:var(--radius);margin-bottom:1.5rem;display:flex;align-items:center;gap:0.65rem;">
                        <i class="fa-solid fa-circle-check"></i> Thank you for your review! It has been submitted for approval.
                    </div>
                <?php elseif ($reviewMsg === 'invalid'): ?>
                    <div style="background:#FEE2E2;color:#991B1B;border:1px solid #FCA5A5;padding:1rem;border-radius:var(--radius);margin-bottom:1.5rem;display:flex;align-items:center;gap:0.65rem;">
                        <i class="fa-solid fa-circle-exclamation"></i> Please fill in all fields and select a rating.
                    </div>
                <?php endif; ?>

                <form method="POST" style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);padding:1.75rem;">
                    <input type="hidden" name="review_submit" value="1">

                    <div style="margin-bottom:1.25rem;">
                        <label style="display:block;font-size:0.8rem;font-weight:600;text-transform:uppercase;letter-spacing:0.06em;color:var(--text-muted);margin-bottom:0.5rem;">Your Name</label>
                        <input type="text" name="reviewer_name" required placeholder="John Doe"
                               value="<?php echo clean($_SESSION['user_name'] ?? ''); ?>"
                               style="width:100%;padding:0.8rem 1rem;border:1.5px solid var(--border);border-radius:var(--radius);font-size:0.95rem;background:var(--bg-card);color:var(--text);font-family:var(--font-body);">
                    </div>

                    <div style="margin-bottom:1.25rem;">
                        <label style="display:block;font-size:0.8rem;font-weight:600;text-transform:uppercase;letter-spacing:0.06em;color:var(--text-muted);margin-bottom:0.5rem;">Rating</label>
                        <div id="starPicker" style="display:flex;gap:0.5rem;margin-bottom:0.5rem;">
                            <?php for ($i=1;$i<=5;$i++): ?>
                            <i class="fa-regular fa-star star-pick" data-val="<?php echo $i; ?>" style="font-size:1.75rem;color:#F59E0B;cursor:pointer;transition:transform 0.15s;" onmouseover="hoverStars(<?php echo $i; ?>)" onmouseout="resetStars()" onclick="selectStar(<?php echo $i; ?>)"></i>
                            <?php endfor; ?>
                        </div>
                        <input type="hidden" name="rating" id="ratingInput" value="0">
                    </div>

                    <div style="margin-bottom:1.25rem;">
                        <label style="display:block;font-size:0.8rem;font-weight:600;text-transform:uppercase;letter-spacing:0.06em;color:var(--text-muted);margin-bottom:0.5rem;">Your Review</label>
                        <textarea name="comment" required rows="5" placeholder="What did you think of this product?"
                                  style="width:100%;padding:0.8rem 1rem;border:1.5px solid var(--border);border-radius:var(--radius);font-size:0.95rem;background:var(--bg-card);color:var(--text);font-family:var(--font-body);resize:vertical;"></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">
                        <i class="fa-solid fa-paper-plane"></i> Submit Review
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- RELATED PRODUCTS -->
<?php if (!empty($related)): ?>
<section style="padding:4rem 0;border-top:1px solid var(--border);">
    <div class="container">
        <h2 style="font-family:var(--font-display);font-size:2rem;font-weight:600;margin-bottom:0.5rem;text-align:center;">You May Also Like</h2>
        <p style="text-align:center;color:var(--text-muted);margin-bottom:2.5rem;">More from <?php echo clean($product['category_name']); ?></p>
        <div class="grid-4">
            <?php foreach ($related as $item):
                $itemPrice = !empty($item['sale_price']) ? $item['sale_price'] : $item['price'];
            ?>
            <div class="product-card fade-in">
                <?php if (!empty($item['tag'])): ?>
                    <span class="product-card__tag <?php echo strtolower($item['tag']); ?>"><?php echo clean($item['tag']); ?></span>
                <?php endif; ?>
                <?php if (!empty($item['sale_price'])): $d=round((1-$item['sale_price']/$item['price'])*100); ?>
                    <span style="position:absolute;top:0.75rem;left:0.75rem;background:var(--danger);color:white;font-size:0.72rem;font-weight:700;padding:0.25rem 0.6rem;border-radius:var(--radius-sm);z-index:2;">-<?php echo $d; ?>%</span>
                <?php endif; ?>
                <div class="product-card__img-wrap">
                    <a href="product.php?slug=<?php echo clean($item['slug']); ?>">
                        <img class="product-card__img"
                             src="<?php echo !empty($item['image'])?clean($item['image']):'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?w=600&q=80'; ?>"
                             alt="<?php echo clean($item['name']); ?>" loading="lazy">
                    </a>
                </div>
                <div class="product-card__body">
                    <p class="product-card__category"><?php echo clean($item['category_name']); ?></p>
                    <h3 class="product-card__name">
                        <a href="product.php?slug=<?php echo clean($item['slug']); ?>" style="color:var(--text);"><?php echo clean($item['name']); ?></a>
                    </h3>
                    <?php if ($item['stock'] > 0 && $item['stock'] <= 5): ?>
                        <p style="font-size:0.72rem;color:#D97706;font-weight:700;margin-bottom:0.4rem;"><i class="fa-solid fa-fire"></i> Only <?php echo $item['stock']; ?> left!</p>
                    <?php endif; ?>
                    <div class="product-card__footer">
                        <div>
                            <span class="product-card__price" data-price="<?php echo $itemPrice; ?>"><?php echo formatPrice($itemPrice); ?></span>
                            <?php if (!empty($item['sale_price'])): ?>
                                <span style="text-decoration:line-through;color:var(--text-muted);font-size:0.8rem;margin-left:0.35rem;" data-price="<?php echo $item['price']; ?>"><?php echo formatPrice($item['price']); ?></span>
                            <?php endif; ?>
                        </div>
                        <button class="btn btn-primary btn-sm" data-add-to-cart="<?php echo $item['id']; ?>">
                            <i class="fa-solid fa-bag-shopping"></i>
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<style>
.gallery-nav{position:absolute;top:50%;transform:translateY(-50%);width:40px;height:40px;background:rgba(255,255,255,0.9);border:none;border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;z-index:3;font-size:0.9rem;color:var(--text);transition:all 0.25s;box-shadow:var(--shadow-sm);}
.gallery-nav:hover{background:var(--primary);color:white;}
.thumb-item.active{border-color:var(--primary)!important;}
.wishlisted{background:var(--danger)!important;color:white!important;border-color:var(--danger)!important;}
@keyframes pulse{0%,100%{opacity:1;}50%{opacity:0.7;}}
</style>

<script>
// Gallery
let galleryImgs = <?php echo json_encode(array_values($galleryImages)); ?>;
let curImg = 0;
function setMainImg(n) {
    curImg = n;
    const img = document.getElementById('mainProductImg');
    img.style.opacity = '0';
    setTimeout(() => { img.src = galleryImgs[n]; img.style.opacity = '1'; }, 150);
    document.querySelectorAll('.thumb-item').forEach((t,i) => {
        t.classList.toggle('active', i===n);
        t.style.borderColor = i===n ? 'var(--primary)' : 'var(--border)';
    });
}
function changeMainImg(dir) {
    setMainImg((curImg + dir + galleryImgs.length) % galleryImgs.length);
}

// Star picker
let selectedRating = 0;
function hoverStars(n) {
    document.querySelectorAll('.star-pick').forEach((s,i) => {
        s.className = i<n ? 'fa-solid fa-star star-pick' : 'fa-regular fa-star star-pick';
    });
}
function resetStars() {
    document.querySelectorAll('.star-pick').forEach((s,i) => {
        s.className = i<selectedRating ? 'fa-solid fa-star star-pick' : 'fa-regular fa-star star-pick';
    });
}
function selectStar(n) {
    selectedRating = n;
    document.getElementById('ratingInput').value = n;
    resetStars();
}

// Wishlist
function toggleWishlist(btn, productId) {
    const isActive = btn.classList.toggle('wishlisted');
    btn.innerHTML = isActive ? '<i class="fa-solid fa-heart"></i>' : '<i class="fa-regular fa-heart"></i>';
    fetch('../php/api/wishlist-toggle.php', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({product_id: productId})
    }).then(r=>r.json()).then(d=>showToast(d.message||(isActive?'Added to wishlist':'Removed from wishlist'),'success')).catch(()=>{});
}
</script>

<?php require_once '../php/includes/footer.php'; ?>
