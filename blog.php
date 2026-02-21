<?php include 'includes/header.php'; ?>

<!-- Page Banner -->
<section class="page-banner">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="page-banner-content">
                    <h2>Blog</h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Blog</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
// Check if specific blog post ID is provided
$singlePost = false;
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $postId = (int)$_GET['id'];
    $post = getBlogPostById($pdo, $postId);
    if ($post) {
        $singlePost = true;
    }
}

// Display single blog post
if ($singlePost) :
    $postDate = new DateTime($post['created_at']);
?>

<!-- Single Blog Post Section -->
<section class="single-post-section section-padding">
    <div class="container">
        <div class="row">
            <div class="col-lg-8">
                <div class="single-post-content" data-aos="fade-up">
                    <div class="post-img">
                        <?php if(isset($post['media_type']) && $post['media_type'] == 'image' && !empty($post['media_path'])): ?>
                            <img src="<?php echo $post['media_path']; ?>" alt="<?php echo $post['title']; ?>" class="img-fluid rounded">
                        <?php elseif(isset($post['media_type']) && $post['media_type'] == 'video' && !empty($post['media_path'])): ?>
                            <video controls class="img-fluid rounded" width="100%">
                                <source src="<?php echo $post['media_path']; ?>" type="video/mp4">
                                Your browser does not support the video tag.
                            </video>
                        <?php else: ?>
                            <!-- Completely removed the placeholder div for text-only posts -->
                        <?php endif; ?>
                    </div>
                    <div class="post-meta">
                        <span><i class="far fa-calendar-alt"></i> <?php echo $postDate->format('M d, Y'); ?></span>
                    </div>
                    <h2><?php echo $post['title']; ?></h2>
                    <div class="post-content">
                        <?php echo nl2br($post['content']); ?>
                    </div>
                    
                    <div class="post-share mt-4">
                        <h4>Share This Post</h4>
                        <div class="social-share">
                            <a href="#"><i class="fab fa-facebook-f"></i></a>
                            <a href="#"><i class="fab fa-twitter"></i></a>
                            <a href="#"><i class="fab fa-linkedin-in"></i></a>
                            <a href="#"><i class="fab fa-instagram"></i></a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="blog-sidebar" data-aos="fade-up">
                    <div class="widget">
                        <h3>Recent Posts</h3>
                        <ul class="recent-posts">
                            <?php
                            $recentPosts = getRecentBlogPosts($pdo, 5);
                            foreach ($recentPosts as $recentPost) :
                                if ($recentPost['id'] != $post['id']) :
                                    $recentDate = new DateTime($recentPost['created_at']);
                            ?>
                            <li>
                                <a href="blog.php?id=<?php echo $recentPost['id']; ?>">
                                    <div class="post-thumb">
                                        <img src="<?php echo isset($recentPost['media_path']) ? $recentPost['media_path'] : ''; ?>" alt="<?php echo $recentPost['title']; ?>">
                                    </div>
                                    <div class="post-info">
                                        <h4><?php echo $recentPost['title']; ?></h4>
                                        <span><?php echo $recentDate->format('M d, Y'); ?></span>
                                    </div>
                                </a>
                            </li>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php else : ?>

<!-- Blog Posts Section (Newsfeed Style) -->
<section class="blog-section section-padding">
    <div class="container">
        <div class="section-title" data-aos="fade-up">
            <h2>Our Blog</h2>
            <p>Latest news and insights</p>
        </div>
        
        <div class="row">
            <?php
            $stmt = $pdo->query("SELECT * FROM blog_posts ORDER BY created_at DESC");
            $blogPosts = $stmt->fetchAll();
            
            if (count($blogPosts) > 0) :
                foreach ($blogPosts as $post) :
                    $postDate = new DateTime($post['created_at']);
            ?>
            <div class="col-lg-12 mb-4" data-aos="fade-up">
                <div class="blog-card newsfeed-card">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="blog-img">
                                <?php if($post['media_type'] == 'image' && $post['media_path']): ?>
                                    <img src="<?php echo $post['media_path']; ?>" alt="<?php echo $post['title']; ?>" class="img-fluid rounded">
                                <?php elseif($post['media_type'] == 'video' && $post['media_path']): ?>
                                    <video controls class="img-fluid rounded">
                                        <source src="<?php echo $post['media_path']; ?>" type="video/mp4">
                                        Your browser does not support the video tag.
                                    </video>
                                <?php else: ?>
                                    <!-- Completely removed the placeholder div for text-only posts -->
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="blog-content">
                                <div class="blog-meta">
                                    <span><i class="far fa-calendar-alt"></i> <?php echo $postDate->format('M d, Y'); ?></span>
                                </div>
                                <h3><a href="blog.php?id=<?php echo $post['id']; ?>"><?php echo $post['title']; ?></a></h3>
                                <div class="blog-excerpt">
                                    <?php echo substr(strip_tags($post['content']), 0, 300) . '...'; ?>
                                </div>
                                <!-- Removed "Read More" link as requested -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php 
                endforeach;
            else :
            ?>
            <div class="col-12">
                <div class="alert alert-info">No blog posts available at the moment.</div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php endif; ?>

<?php include 'includes/footer.php'; ?>