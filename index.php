<?php
$pageTitle = 'Home';
require_once 'php/config/database.php';
require_once 'php/config/helpers.php';
require_once 'php/includes/header.php';

$featuredProducts = getProducts($pdo, ['featured' => true, 'limit' => 8]);
$categories       = getCategories($pdo);
?>


<section style="position:relative; min-height:92vh; display:flex; align-items:center; overflow:hidden;">
    <div class="hero-slides">
        <div class="hero-slide active" style="background-image:url('https://images.unsplash.com/photo-1616046229478-9901c5536a45?w=1600&q=80');"></div>
        <div class="hero-slide" style="background-image:url('https://images.unsplash.com/photo-1555041469-a586c61ea9bc?w=1600&q=80');"></div>
        <div class="hero-slide" style="background-image:url('https://images.unsplash.com/photo-1586023492125-27b2c045efd7?w=1600&q=80');"></div>
    </div>
    <div style="position:absolute;inset:0;background:linear-gradient(to bottom,rgba(0,0,0,0.2) 0%,rgba(0,0,0,0.55) 100%);"></div>
    <div class="container" style="position:relative;z-index:2;text-align:center;padding:4rem 1.5rem;width:100%;">
        <span style="display:inline-block;background:rgba(166,124,82,0.9);color:white;font-size:0.75rem;letter-spacing:0.3em;text-transform:uppercase;padding:0.5rem 1.5rem;border-radius:2rem;margin-bottom:1.5rem;">
            New Collection 2025
        </span>
        <h1 style="font-family:var(--font-display);font-size:clamp(2.5rem,6vw,5rem);font-weight:700;color:white;line-height:1.1;margin-bottom:1.5rem;text-shadow:0 2px 20px rgba(0,0,0,0.3);">
            Beautiful Pieces<br>for Every Home
        </h1>
        <p style="font-size:clamp(1rem,2vw,1.2rem);color:rgba(255,255,255,0.88);max-width:540px;margin:0 auto 2.5rem;line-height:1.8;">
            Discover our curated collection of premium home decor. Timeless style, sustainable materials, delivered to your door.
        </p>
        <div style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;">
            <a href="pages/shop.php" class="btn btn-primary" style="font-size:1rem;padding:1rem 2.5rem;border-radius:50px;">
                <i class="fa-solid fa-bag-shopping"></i> Shop Collection
            </a>
            <a href="pages/shop.php?sort=price_asc" class="btn btn-outline" style="font-size:1rem;padding:1rem 2.5rem;border-radius:50px;border-color:white;color:white;">
                <i class="fa-solid fa-tag"></i> Best Prices
            </a>
        </div>
        <div style="margin-top:3rem;display:flex;gap:0.5rem;justify-content:center;" id="slideDots">
            <button class="slide-dot active" onclick="goToSlide(0)"></button>
            <button class="slide-dot" onclick="goToSlide(1)"></button>
            <button class="slide-dot" onclick="goToSlide(2)"></button>
        </div>
    </div>
    <div style="position:absolute;bottom:2rem;left:50%;transform:translateX(-50%);color:rgba(255,255,255,0.6);font-size:0.75rem;text-align:center;z-index:2;animation:bounce 2s infinite;">
        <i class="fa-solid fa-chevron-down" style="font-size:1.25rem;display:block;margin-bottom:0.25rem;"></i>Scroll
    </div>
</section>

<!-- ===== TRUST BAR ===== -->
<section style="background:var(--text);padding:1.5rem 0;">
    <div class="container">
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;text-align:center;">
            <?php
            $trustItems = [
                ['fa-truck','Free Shipping','On orders over KES 5,000'],
                ['fa-rotate-left','Easy Returns','30-day return policy'],
                ['fa-shield-halved','Secure Payment','Protected by Pesapal'],
                ['fa-headset','24/7 Support','Always here to help'],
            ];
            foreach ($trustItems as $t):
            ?>
            <div style="display:flex;align-items:center;justify-content:center;gap:0.75rem;color:rgba(255,255,255,0.8);font-size:0.85rem;">
                <i class="fa-solid <?php echo $t[0]; ?>" style="color:var(--primary);font-size:1.3rem;flex-shrink:0;"></i>
                <span><strong style="color:white;display:block;font-size:0.9rem;"><?php echo $t[1]; ?></strong><?php echo $t[2]; ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php if (!empty($categories)): ?>
<section class="section">
    <div class="container">
        <div style="text-align:center;margin-bottom:3rem;">
            <span style="font-size:0.78rem;letter-spacing:0.25em;text-transform:uppercase;color:var(--primary);font-weight:600;">Browse</span>
            <h2 style="font-family:var(--font-display);font-size:2.5rem;font-weight:600;margin-top:0.5rem;" class="fade-in">Shop by Category</h2>
            <p style="color:var(--text-muted);margin-top:0.5rem;" class="fade-in">Find exactly what you are looking for</p>
        </div>
        <div class="category-masonry">
            <?php foreach ($categories as $i => $cat): ?>
                <a href="pages/shop.php?category=<?php echo $cat['id']; ?>"
                   class="category-card-new fade-in <?php echo $i === 0 ? 'category-featured' : ''; ?>">
                    <?php if (!empty($cat['image'])): ?>
                        <img src="<?php echo clean($cat['image']); ?>" alt="<?php echo clean($cat['name']); ?>" loading="lazy">
                    <?php else: ?>
                        <div style="width:100%;height:100%;min-height:220px;background:var(--bg-alt);display:flex;align-items:center;justify-content:center;">
                            <i class="fa-solid fa-couch" style="font-size:3rem;color:var(--primary);"></i>
                        </div>
                    <?php endif; ?>
                    <div class="cat-overlay">
                        <h3><?php echo clean($cat['name']); ?></h3>
                        <?php if (!empty($cat['description'])): ?>
                            <p><?php echo clean($cat['description']); ?></p>
                        <?php endif; ?>
                        <span class="cat-btn">Shop Now <i class="fa-solid fa-arrow-right"></i></span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ===== FEATURED PRODUCTS ===== -->
<?php if (!empty($featuredProducts)): ?>
<section class="section" style="background:var(--bg-alt);border-top:1px solid var(--border);border-bottom:1px solid var(--border);">
    <div class="container">
        <div style="display:flex;align-items:flex-end;justify-content:space-between;margin-bottom:3rem;flex-wrap:wrap;gap:1rem;">
            <div>
                <span style="font-size:0.78rem;letter-spacing:0.25em;text-transform:uppercase;color:var(--primary);font-weight:600;">Handpicked</span>
                <h2 style="font-family:var(--font-display);font-size:2.5rem;font-weight:600;margin-top:0.5rem;" class="fade-in">Featured Products</h2>
            </div>
            <a href="pages/shop.php" class="btn btn-outline">View All <i class="fa-solid fa-arrow-right"></i></a>
        </div>
        <div class="grid-4">
            <?php foreach ($featuredProducts as $product):
                $price = !empty($product['sale_price']) ? $product['sale_price'] : $product['price'];
            ?>
                <div class="product-card fade-in">
                    <?php if (!empty($product['tag'])): ?>
                        <span class="product-card__tag <?php echo strtolower($product['tag']); ?>"><?php echo clean($product['tag']); ?></span>
                    <?php endif; ?>
                    <button class="wishlist-btn" onclick="toggleWishlist(event,this,<?php echo $product['id']; ?>)" title="Wishlist">
                        <i class="fa-regular fa-heart"></i>
                    </button>
                    <div class="product-card__img-wrap">
                        <a href="pages/product.php?slug=<?php echo clean($product['slug']); ?>">
                            <img class="product-card__img"
                                 src="<?php echo !empty($product['image']) ? clean($product['image']) : 'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?w=600&q=80'; ?>"
                                 alt="<?php echo clean($product['name']); ?>" loading="lazy">
                        </a>
                    </div>
                    <div class="product-card__body">
                        <p class="product-card__category"><?php echo clean($product['category_name'] ?? ''); ?></p>
                        <h3 class="product-card__name">
                            <a href="pages/product.php?slug=<?php echo clean($product['slug']); ?>" style="color:var(--text);"><?php echo clean($product['name']); ?></a>
                        </h3>
                        <?php if ($product['stock'] > 0 && $product['stock'] <= 5): ?>
                            <p style="font-size:0.75rem;color:#D97706;font-weight:600;margin-bottom:0.5rem;"><i class="fa-solid fa-fire"></i> Only <?php echo $product['stock']; ?> left!</p>
                        <?php elseif ($product['stock'] <= 0): ?>
                            <p style="font-size:0.75rem;color:var(--danger);font-weight:600;margin-bottom:0.5rem;"><i class="fa-solid fa-ban"></i> Out of Stock</p>
                        <?php endif; ?>
                        <div class="product-card__footer">
                            <div>
                                <span class="product-card__price" data-price="<?php echo $price; ?>"><?php echo formatPrice($price); ?></span>
                                <?php if (!empty($product['sale_price'])): ?>
                                    <span style="text-decoration:line-through;color:var(--text-muted);font-size:0.82rem;margin-left:0.4rem;" data-price="<?php echo $product['price']; ?>"><?php echo formatPrice($product['price']); ?></span>
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
    </div>
</section>
<?php endif; ?>

<!-- ===== WHY CHOOSE US ===== -->
<section class="section">
    <div class="container">
        <div style="text-align:center;margin-bottom:3rem;">
            <span style="font-size:0.78rem;letter-spacing:0.25em;text-transform:uppercase;color:var(--primary);font-weight:600;">Why Us</span>
            <h2 style="font-family:var(--font-display);font-size:2.5rem;font-weight:600;margin-top:0.5rem;" class="fade-in">The Maison Decor Difference</h2>
        </div>
        <div class="grid-3">
            <?php
            $why = [
                ['fa-award','Premium Quality','Every product is carefully selected and quality checked before it reaches your home.'],
                ['fa-headset','Expert Support','Our friendly team is always ready to help you find the perfect piece for your space.'],
                ['fa-globe','Multi-Currency','View prices in KES, USD, EUR, GBP and many more currencies instantly.'],
                ['fa-leaf','Sustainably Sourced','We care about the planet. All our products use eco-friendly sustainable materials.'],
                ['fa-truck-fast','Fast Delivery','Orders dispatched within 24 hours and delivered in 3-7 business days.'],
                ['fa-mobile-screen','M-Pesa Accepted','Pay easily with M-Pesa, Visa, Mastercard and more through Pesapal.'],
            ];
            foreach ($why as $w):
            ?>
            <div class="fade-in why-card">
                <div style="width:64px;height:64px;background:var(--primary-light);border-radius:var(--radius-lg);display:flex;align-items:center;justify-content:center;margin:0 auto 1.25rem;">
                    <i class="fa-solid <?php echo $w[0]; ?>" style="font-size:1.5rem;color:var(--primary);"></i>
                </div>
                <h3 style="font-family:var(--font-display);font-size:1.1rem;margin-bottom:0.75rem;"><?php echo $w[1]; ?></h3>
                <p style="color:var(--text-muted);font-size:0.88rem;line-height:1.7;"><?php echo $w[2]; ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ===== NEWSLETTER ===== -->
<section style="background:linear-gradient(135deg,var(--primary) 0%,var(--primary-dark) 100%);padding:5rem 0;">
    <div class="container" style="text-align:center;max-width:560px;margin:0 auto;">
        <i class="fa-solid fa-envelope-open-text" style="font-size:2.5rem;color:rgba(255,255,255,0.8);margin-bottom:1rem;display:block;"></i>
        <h2 style="font-family:var(--font-display);font-size:2.25rem;color:white;margin-bottom:0.75rem;">Stay in the Loop</h2>
        <p style="color:rgba(255,255,255,0.75);margin-bottom:2rem;font-size:0.95rem;line-height:1.8;">
            Subscribe to get exclusive deals, new arrivals and home decor inspiration.
        </p>
        <div style="display:flex;gap:0.75rem;max-width:420px;margin:0 auto;">
            <input type="email" id="newsletterEmail" placeholder="Your email address"
                   style="flex:1;padding:0.9rem 1.25rem;border:2px solid rgba(255,255,255,0.4);border-radius:50px;font-size:0.95rem;background:rgba(255,255,255,0.15);color:white;outline:none;font-family:var(--font-body);"
                   onfocus="this.style.borderColor='white'" onblur="this.style.borderColor='rgba(255,255,255,0.4)'"
                   onkeypress="if(event.key==='Enter')subscribeNewsletter()">
            <button onclick="subscribeNewsletter()" class="btn" style="background:white;color:var(--primary);font-weight:700;border-radius:50px;white-space:nowrap;padding:0.9rem 1.75rem;">
                <i class="fa-solid fa-paper-plane"></i> Subscribe
            </button>
        </div>
        <p style="color:rgba(255,255,255,0.5);font-size:0.78rem;margin-top:1rem;">No spam. Unsubscribe at any time.</p>
    </div>
</section>

<style>
.hero-slides{position:absolute;inset:0;}
.hero-slide{position:absolute;inset:0;background-size:cover;background-position:center;opacity:0;transition:opacity 1.2s ease;}
.hero-slide.active{opacity:1;}
.slide-dot{width:10px;height:10px;border-radius:50%;border:2px solid rgba(255,255,255,0.6);background:transparent;cursor:pointer;transition:all 0.3s;padding:0;}
.slide-dot.active{background:white;border-color:white;width:28px;border-radius:5px;}
.category-masonry{display:grid;grid-template-columns:repeat(4,1fr);gap:1.25rem;}
.category-card-new{position:relative;border-radius:var(--radius-lg);overflow:hidden;aspect-ratio:3/4;display:block;cursor:pointer;box-shadow:var(--shadow-sm);}
.category-featured{grid-column:span 2;grid-row:span 2;aspect-ratio:auto;}
.category-card-new img{width:100%;height:100%;object-fit:cover;transition:transform 0.5s ease;}
.category-card-new:hover img{transform:scale(1.06);}
.cat-overlay{position:absolute;bottom:0;left:0;right:0;background:linear-gradient(to top,rgba(0,0,0,0.72) 0%,transparent 100%);padding:2rem 1.5rem 1.5rem;}
.category-featured .cat-overlay{padding:3rem 2rem 2rem;}
.cat-overlay h3{font-family:var(--font-display);color:white;font-size:1.2rem;font-weight:700;margin-bottom:0.35rem;}
.category-featured .cat-overlay h3{font-size:1.8rem;}
.cat-overlay p{color:rgba(255,255,255,0.8);font-size:0.82rem;margin-bottom:0.75rem;line-height:1.5;}
.cat-btn{display:inline-flex;align-items:center;gap:0.5rem;background:rgba(255,255,255,0.18);color:white;border:1px solid rgba(255,255,255,0.5);padding:0.4rem 1rem;border-radius:50px;font-size:0.8rem;font-weight:600;backdrop-filter:blur(4px);transition:all 0.3s;}
.category-card-new:hover .cat-btn{background:var(--primary);border-color:var(--primary);}
.wishlist-btn{position:absolute;top:0.75rem;right:0.75rem;z-index:3;width:34px;height:34px;border-radius:50%;background:rgba(255,255,255,0.92);border:none;display:flex;align-items:center;justify-content:center;color:var(--text-muted);font-size:0.9rem;cursor:pointer;transition:all 0.25s;box-shadow:var(--shadow-sm);opacity:0;}
.product-card:hover .wishlist-btn{opacity:1;}
.wishlist-btn.wishlisted{color:var(--danger);background:white;opacity:1;}
.wishlist-btn:hover{transform:scale(1.1);color:var(--danger);}
.why-card{background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);padding:2rem;text-align:center;transition:transform 0.25s,box-shadow 0.25s;}
.why-card:hover{transform:translateY(-4px);box-shadow:var(--shadow-md);}
@keyframes bounce{0%,100%{transform:translateX(-50%) translateY(0);}50%{transform:translateX(-50%) translateY(8px);}}
@media(max-width:1024px){.category-masonry{grid-template-columns:repeat(2,1fr);}}
@media(max-width:640px){.category-masonry{grid-template-columns:1fr 1fr;}.category-featured{grid-column:span 2;}}
</style>

<script>
// Hero slideshow
let cur=0;
const slides=document.querySelectorAll('.hero-slide');
const dots=document.querySelectorAll('.slide-dot');
function goToSlide(n){slides[cur].classList.remove('active');dots[cur].classList.remove('active');cur=n;slides[cur].classList.add('active');dots[cur].classList.add('active');}
setInterval(()=>goToSlide((cur+1)%slides.length),5000);

// Newsletter
function subscribeNewsletter(){
    const input=document.getElementById('newsletterEmail');
    const email=input.value.trim();
    if(!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)){showToast('Please enter a valid email','error');return;}
    fetch('php/api/newsletter-subscribe.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({email})})
    .then(r=>r.json()).then(d=>{showToast(d.message||'Thank you!',d.success?'success':'error');if(d.success)input.value='';})
    .catch(()=>{showToast('Thank you for subscribing!','success');input.value='';});
}

// Wishlist
function toggleWishlist(e,btn,productId){
    e.preventDefault();e.stopPropagation();
    const active=btn.classList.toggle('wishlisted');
    btn.innerHTML=active?'<i class="fa-solid fa-heart"></i>':'<i class="fa-regular fa-heart"></i>';
    fetch('php/api/wishlist-toggle.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({product_id:productId})})
    .then(r=>r.json()).then(d=>showToast(d.message||(active?'Added to wishlist':'Removed from wishlist'),'success')).catch(()=>{});
}
</script>

<?php require_once 'php/includes/footer.php'; ?>
