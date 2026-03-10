<?php
$pageTitle = 'Shop';
require_once '../php/config/database.php';
require_once '../php/config/helpers.php';
require_once '../php/includes/header.php';

$search     = trim($_GET['search']   ?? '');
$categoryId = (int)($_GET['category'] ?? 0);
$sort       = $_GET['sort']           ?? '';
$minPrice   = (float)($_GET['min_price'] ?? 0);
$maxPrice   = (float)($_GET['max_price'] ?? 9999);
$categories = getCategories($pdo);

// Get all products for price range
$allPrices  = $pdo->query("SELECT MIN(price) as mn, MAX(price) as mx FROM products")->fetch();
$globalMin  = (float)($allPrices['mn'] ?? 0);
$globalMax  = (float)($allPrices['mx'] ?? 500);

// Build query with price filter
$where  = ['1=1'];
$params = [];
if ($search) { $where[] = 'p.name LIKE ?'; $params[] = '%'.$search.'%'; }
if ($categoryId) { $where[] = 'p.category_id = ?'; $params[] = $categoryId; }
if ($minPrice > 0) { $where[] = 'p.price >= ?'; $params[] = $minPrice; }
if ($maxPrice < 9999) { $where[] = 'p.price <= ?'; $params[] = $maxPrice; }

$order = 'p.created_at DESC';
if ($sort === 'price_asc')  $order = 'p.price ASC';
if ($sort === 'price_desc') $order = 'p.price DESC';
if ($sort === 'name_asc')   $order = 'p.name ASC';
if ($sort === 'popular')    $order = 'p.featured DESC, p.created_at DESC';

$sql  = "SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id=c.id WHERE ".implode(' AND ',$where)." ORDER BY $order";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Wishlist session IDs
$sessionId = getCartSessionId();
$wishlistStmt = $pdo->prepare("SELECT product_id FROM wishlist WHERE session_id=?");
$wishlistStmt->execute([$sessionId]);
$wishlistIds = array_column($wishlistStmt->fetchAll(), 'product_id');
?>

<!-- PAGE BANNER -->
<section style="background:var(--bg-alt);border-bottom:1px solid var(--border);padding:2.5rem 0;">
    <div class="container">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
            <div>
                <h1 style="font-family:var(--font-display);font-size:2.25rem;font-weight:600;margin-bottom:0.35rem;">Shop All Products</h1>
                <p style="color:var(--text-muted);font-size:0.9rem;">
                    <a href="../index.php" style="color:var(--text-muted);">Home</a>
                    <i class="fa-solid fa-chevron-right" style="font-size:0.6rem;margin:0 0.5rem;"></i>
                    Shop
                    <?php if ($categoryId): $activeCat = array_filter($categories, fn($c) => $c['id']==$categoryId); $activeCat = reset($activeCat); ?>
                    <i class="fa-solid fa-chevron-right" style="font-size:0.6rem;margin:0 0.5rem;"></i>
                    <?php echo clean($activeCat['name'] ?? ''); ?>
                    <?php endif; ?>
                </p>
            </div>
            <!-- Mobile Filter Toggle -->
            <button onclick="toggleMobileFilters()" class="btn btn-outline" style="display:none;" id="mobileFilterBtn">
                <i class="fa-solid fa-sliders"></i> Filters
            </button>
        </div>
    </div>
</section>

<!-- SHOP CONTENT -->
<section class="section" style="padding-top:2.5rem;">
    <div class="container">
        <div style="display:grid;grid-template-columns:280px 1fr;gap:2.5rem;align-items:start;">

            <!-- SIDEBAR -->
            <aside id="shopSidebar" style="position:sticky;top:90px;">
                <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);padding:1.5rem;">

                    <!-- Search -->
                    <div style="margin-bottom:1.75rem;">
                        <h4 class="filter-heading"><i class="fa-solid fa-magnifying-glass"></i> Search</h4>
                        <div style="position:relative;">
                            <input type="text" id="searchInput" placeholder="Search products..."
                                   value="<?php echo clean($search); ?>"
                                   style="width:100%;padding:0.7rem 2.5rem 0.7rem 1rem;border:1.5px solid var(--border);border-radius:var(--radius);font-size:0.9rem;background:var(--bg);color:var(--text);font-family:var(--font-body);">
                            <i class="fa-solid fa-magnifying-glass" style="position:absolute;right:0.85rem;top:50%;transform:translateY(-50%);color:var(--text-muted);font-size:0.8rem;"></i>
                        </div>
                    </div>

                    <!-- Categories -->
                    <div style="margin-bottom:1.75rem;">
                        <h4 class="filter-heading"><i class="fa-solid fa-tags"></i> Categories</h4>
                        <ul style="list-style:none;">
                            <li style="margin-bottom:0.3rem;">
                                <a href="shop.php<?php echo $sort?'?sort='.$sort:''; ?>"
                                   class="cat-filter-link <?php echo !$categoryId?'active':''; ?>">
                                    <i class="fa-solid fa-grid-2"></i> All Products
                                    <span class="cat-count"><?php echo $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn(); ?></span>
                                </a>
                            </li>
                            <?php foreach ($categories as $cat):
                                $catCount = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id=?");
                                $catCount->execute([$cat['id']]);
                                $cnt = $catCount->fetchColumn();
                            ?>
                            <li style="margin-bottom:0.3rem;">
                                <a href="shop.php?category=<?php echo $cat['id']; ?><?php echo $sort?'&sort='.$sort:''; ?>"
                                   class="cat-filter-link <?php echo $categoryId==$cat['id']?'active':''; ?>">
                                    <?php echo clean($cat['name']); ?>
                                    <span class="cat-count"><?php echo $cnt; ?></span>
                                </a>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <!-- Price Range Slider -->
                    <div style="margin-bottom:1.75rem;">
                        <h4 class="filter-heading"><i class="fa-solid fa-dollar-sign"></i> Price Range</h4>
                        <div style="padding:0 0.5rem;">
                            <div style="display:flex;justify-content:space-between;margin-bottom:0.75rem;font-size:0.85rem;">
                                <span style="font-weight:600;color:var(--primary);" id="priceMinLabel">$<?php echo number_format($minPrice>0?$minPrice:$globalMin,2); ?></span>
                                <span style="font-weight:600;color:var(--primary);" id="priceMaxLabel">$<?php echo number_format($maxPrice<9999?$maxPrice:$globalMax,2); ?></span>
                            </div>
                            <div style="position:relative;height:6px;background:var(--border);border-radius:3px;margin:1rem 0;">
                                <div id="priceTrack" style="position:absolute;height:6px;background:var(--primary);border-radius:3px;"></div>
                                <input type="range" id="priceMin"
                                       min="<?php echo $globalMin; ?>" max="<?php echo $globalMax; ?>"
                                       step="1" value="<?php echo $minPrice>0?$minPrice:$globalMin; ?>"
                                       style="position:absolute;width:100%;height:6px;opacity:0;cursor:pointer;z-index:3;"
                                       oninput="updatePriceSlider()">
                                <input type="range" id="priceMax"
                                       min="<?php echo $globalMin; ?>" max="<?php echo $globalMax; ?>"
                                       step="1" value="<?php echo $maxPrice<9999?$maxPrice:$globalMax; ?>"
                                       style="position:absolute;width:100%;height:6px;opacity:0;cursor:pointer;z-index:4;"
                                       oninput="updatePriceSlider()">
                                <div id="thumbMin" class="price-thumb" style="left:0%;"></div>
                                <div id="thumbMax" class="price-thumb" style="left:100%;"></div>
                            </div>
                            <button onclick="applyPriceFilter()" class="btn btn-primary" style="width:100%;justify-content:center;margin-top:0.75rem;padding:0.65rem;">
                                Apply Filter
                            </button>
                        </div>
                    </div>

                    <!-- Sort -->
                    <div>
                        <h4 class="filter-heading"><i class="fa-solid fa-arrow-up-wide-short"></i> Sort By</h4>
                        <div style="display:flex;flex-direction:column;gap:0.3rem;">
                            <?php
                            $sortOptions = [''=> 'Newest First','popular'=>'Most Popular','price_asc'=>'Price: Low to High','price_desc'=>'Price: High to Low','name_asc'=>'Name A–Z'];
                            foreach ($sortOptions as $val => $label):
                            ?>
                            <a href="shop.php?<?php echo $categoryId?'category='.$categoryId.'&':''; ?>sort=<?php echo $val; ?>"
                               class="cat-filter-link <?php echo $sort===$val?'active':''; ?>">
                                <?php echo $label; ?>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Clear All -->
                    <?php if ($search || $categoryId || $sort || $minPrice>0 || $maxPrice<9999): ?>
                    <a href="shop.php" style="display:block;text-align:center;margin-top:1.5rem;padding:0.65rem;border:1.5px dashed var(--border);border-radius:var(--radius);font-size:0.85rem;color:var(--text-muted);">
                        <i class="fa-solid fa-xmark"></i> Clear All Filters
                    </a>
                    <?php endif; ?>
                </div>
            </aside>

            <!-- PRODUCTS GRID -->
            <div>
                <!-- Top bar -->
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem;">
                    <p style="color:var(--text-muted);font-size:0.9rem;">
                        <strong style="color:var(--text);"><?php echo count($products); ?></strong> products found
                        <?php if ($search): ?> for "<strong><?php echo clean($search); ?></strong>"<?php endif; ?>
                    </p>
                    <!-- View toggle -->
                    <div style="display:flex;gap:0.5rem;align-items:center;">
                        <button onclick="setView('grid')" id="viewGrid" class="view-toggle active" title="Grid view"><i class="fa-solid fa-grid-2"></i></button>
                        <button onclick="setView('list')" id="viewList" class="view-toggle" title="List view"><i class="fa-solid fa-list"></i></button>
                    </div>
                </div>

                <?php if (empty($products)): ?>
                    <div style="text-align:center;padding:5rem 2rem;background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);">
                        <i class="fa-solid fa-box-open" style="font-size:3.5rem;color:var(--border);display:block;margin-bottom:1.25rem;"></i>
                        <h3 style="font-family:var(--font-display);margin-bottom:0.5rem;">No products found</h3>
                        <p style="color:var(--text-muted);margin-bottom:1.5rem;">Try a different search or browse all categories.</p>
                        <a href="shop.php" class="btn btn-primary"><i class="fa-solid fa-arrow-left"></i> Browse All Products</a>
                    </div>
                <?php else: ?>
                    <div class="grid-4" id="productsGrid">
                        <?php foreach ($products as $product):
                            $price      = !empty($product['sale_price']) ? $product['sale_price'] : $product['price'];
                            $inWishlist = in_array($product['id'], $wishlistIds);
                        ?>
                            <div class="product-card fade-in">
                                <?php if (!empty($product['tag'])): ?>
                                    <span class="product-card__tag <?php echo strtolower($product['tag']); ?>"><?php echo clean($product['tag']); ?></span>
                                <?php endif; ?>

                                <!-- Wishlist -->
                                <button class="wishlist-btn <?php echo $inWishlist?'wishlisted':''; ?>"
                                        onclick="toggleWishlist(event,this,<?php echo $product['id']; ?>)" title="Wishlist">
                                    <i class="fa-<?php echo $inWishlist?'solid':'regular'; ?> fa-heart"></i>
                                </button>

                                <!-- Discount badge -->
                                <?php if (!empty($product['sale_price'])): $disc=round((1-$product['sale_price']/$product['price'])*100); ?>
                                    <span style="position:absolute;top:0.75rem;left:0.75rem;background:var(--danger);color:white;font-size:0.72rem;font-weight:700;padding:0.25rem 0.6rem;border-radius:var(--radius-sm);z-index:2;">-<?php echo $disc; ?>%</span>
                                <?php endif; ?>

                                <div class="product-card__img-wrap">
                                    <a href="product.php?slug=<?php echo clean($product['slug']); ?>">
                                        <img class="product-card__img"
                                             src="<?php echo !empty($product['image'])?clean($product['image']):'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?w=600&q=80'; ?>"
                                             alt="<?php echo clean($product['name']); ?>" loading="lazy">
                                    </a>
                                    <!-- Quick add overlay -->
                                    <?php if ($product['stock'] > 0): ?>
                                    <div class="quick-add-overlay">
                                        <button class="btn btn-primary" data-add-to-cart="<?php echo $product['id']; ?>"
                                                style="width:100%;justify-content:center;border-radius:0;">
                                            <i class="fa-solid fa-bag-shopping"></i> Quick Add
                                        </button>
                                    </div>
                                    <?php endif; ?>
                                </div>

                                <div class="product-card__body">
                                    <p class="product-card__category"><?php echo clean($product['category_name'] ?? ''); ?></p>
                                    <h3 class="product-card__name">
                                        <a href="product.php?slug=<?php echo clean($product['slug']); ?>" style="color:var(--text);"><?php echo clean($product['name']); ?></a>
                                    </h3>

                                    <!-- Stock badge -->
                                    <?php if ($product['stock'] > 0 && $product['stock'] <= 5): ?>
                                        <p style="font-size:0.72rem;color:#D97706;font-weight:700;margin-bottom:0.4rem;">
                                            <i class="fa-solid fa-fire"></i> Only <?php echo $product['stock']; ?> left!
                                        </p>
                                    <?php elseif ($product['stock'] <= 0): ?>
                                        <p style="font-size:0.72rem;color:var(--danger);font-weight:700;margin-bottom:0.4rem;">
                                            <i class="fa-solid fa-ban"></i> Out of Stock
                                        </p>
                                    <?php endif; ?>

                                    <div class="product-card__footer">
                                        <div>
                                            <span class="product-card__price" data-price="<?php echo $price; ?>"><?php echo formatPrice($price); ?></span>
                                            <?php if (!empty($product['sale_price'])): ?>
                                                <span style="text-decoration:line-through;color:var(--text-muted);font-size:0.8rem;margin-left:0.35rem;" data-price="<?php echo $product['price']; ?>"><?php echo formatPrice($product['price']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <button class="btn btn-primary btn-sm" data-add-to-cart="<?php echo $product['id']; ?>"
                                                <?php echo $product['stock'] <= 0 ? 'disabled style="opacity:0.5;cursor:not-allowed;"' : ''; ?>>
                                            <?php echo $product['stock'] > 0 ? '<i class="fa-solid fa-bag-shopping"></i>' : '<i class="fa-solid fa-ban"></i>'; ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<style>
.filter-heading{font-size:0.78rem;text-transform:uppercase;letter-spacing:0.1em;color:var(--text-muted);margin-bottom:0.85rem;font-weight:700;display:flex;align-items:center;gap:0.5rem;}
.cat-filter-link{display:flex;align-items:center;justify-content:space-between;padding:0.55rem 0.85rem;border-radius:var(--radius-sm);font-size:0.88rem;color:var(--text);transition:all 0.2s;}
.cat-filter-link:hover,.cat-filter-link.active{background:var(--primary);color:white;}
.cat-count{background:var(--bg-alt);color:var(--text-muted);font-size:0.72rem;padding:0.15rem 0.55rem;border-radius:2rem;font-weight:600;}
.cat-filter-link.active .cat-count,.cat-filter-link:hover .cat-count{background:rgba(255,255,255,0.25);color:white;}
.price-thumb{position:absolute;width:16px;height:16px;background:var(--primary);border:2px solid white;border-radius:50%;top:50%;transform:translate(-50%,-50%);box-shadow:var(--shadow-sm);pointer-events:none;z-index:2;}
.view-toggle{width:34px;height:34px;border:1.5px solid var(--border);border-radius:var(--radius-sm);background:var(--bg-card);color:var(--text-muted);display:flex;align-items:center;justify-content:center;cursor:pointer;transition:all 0.2s;}
.view-toggle.active{background:var(--primary);color:white;border-color:var(--primary);}
.wishlist-btn{position:absolute;top:0.75rem;right:0.75rem;z-index:3;width:34px;height:34px;border-radius:50%;background:rgba(255,255,255,0.92);border:none;display:flex;align-items:center;justify-content:center;color:var(--text-muted);font-size:0.9rem;cursor:pointer;transition:all 0.25s;box-shadow:var(--shadow-sm);opacity:0;}
.product-card:hover .wishlist-btn{opacity:1;}
.wishlist-btn.wishlisted{color:var(--danger);opacity:1;}
.wishlist-btn:hover{transform:scale(1.1);color:var(--danger);}
.quick-add-overlay{position:absolute;bottom:0;left:0;right:0;transform:translateY(100%);transition:transform 0.3s ease;z-index:2;overflow:hidden;}
.product-card__img-wrap{position:relative;overflow:hidden;}
.product-card:hover .quick-add-overlay{transform:translateY(0);}
#productsGrid.list-view{display:flex;flex-direction:column;gap:1rem;}
#productsGrid.list-view .product-card{display:grid;grid-template-columns:180px 1fr;border-radius:var(--radius-lg);}
#productsGrid.list-view .product-card__img-wrap{height:180px;}
#productsGrid.list-view .product-card__img-wrap .quick-add-overlay{display:none;}
@media(max-width:768px){
    #shopSidebar{display:none;}
    #shopSidebar.open{display:block;position:fixed;top:0;left:0;height:100vh;width:290px;z-index:200;overflow-y:auto;background:var(--bg-card);border-right:1px solid var(--border);}
    #mobileFilterBtn{display:flex!important;}
}
</style>

<script>
// Price Slider
const globalMin=<?php echo $globalMin; ?>, globalMax=<?php echo $globalMax; ?>;
function updatePriceSlider(){
    const mn=document.getElementById('priceMin'), mx=document.getElementById('priceMax');
    let lo=parseFloat(mn.value), hi=parseFloat(mx.value);
    if(lo>hi){const t=lo;lo=hi;hi=t;}
    const pct1=((lo-globalMin)/(globalMax-globalMin))*100;
    const pct2=((hi-globalMin)/(globalMax-globalMin))*100;
    document.getElementById('priceTrack').style.left=pct1+'%';
    document.getElementById('priceTrack').style.width=(pct2-pct1)+'%';
    document.getElementById('thumbMin').style.left=pct1+'%';
    document.getElementById('thumbMax').style.left=pct2+'%';
    document.getElementById('priceMinLabel').textContent='$'+lo.toFixed(2);
    document.getElementById('priceMaxLabel').textContent='$'+hi.toFixed(2);
}
function applyPriceFilter(){
    const lo=document.getElementById('priceMin').value, hi=document.getElementById('priceMax').value;
    let url='shop.php?min_price='+lo+'&max_price='+hi;
    <?php if ($categoryId): ?>url+='&category=<?php echo $categoryId; ?>';<?php endif; ?>
    <?php if ($sort): ?>url+='&sort=<?php echo $sort; ?>';<?php endif; ?>
    window.location.href=url;
}
updatePriceSlider();

// Search
document.getElementById('searchInput').addEventListener('keypress',function(e){
    if(e.key==='Enter'){const v=this.value.trim();if(v)window.location.href='shop.php?search='+encodeURIComponent(v);}
});

// Grid / List view
function setView(v){
    const grid=document.getElementById('productsGrid');
    const btnGrid=document.getElementById('viewGrid'), btnList=document.getElementById('viewList');
    if(v==='list'){grid.classList.add('list-view');btnList.classList.add('active');btnGrid.classList.remove('active');}
    else{grid.classList.remove('list-view');btnGrid.classList.add('active');btnList.classList.remove('active');}
    localStorage.setItem('shopView',v);
}
(function(){const v=localStorage.getItem('shopView')||'grid';setView(v);})();

// Mobile sidebar toggle
function toggleMobileFilters(){
    document.getElementById('shopSidebar').classList.toggle('open');
}

// Wishlist
function toggleWishlist(e,btn,productId){
    e.preventDefault();e.stopPropagation();
    const active=btn.classList.toggle('wishlisted');
    btn.innerHTML=active?'<i class="fa-solid fa-heart"></i>':'<i class="fa-regular fa-heart"></i>';
    fetch('../php/api/wishlist-toggle.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({product_id:productId})})
    .then(r=>r.json()).then(d=>showToast(d.message||(active?'Added to wishlist':'Removed from wishlist'),'success')).catch(()=>{});
}
</script>

<?php require_once '../php/includes/footer.php'; ?>
