function toggleLoading(show = true) {
    const spinner = document.querySelector('.loading-spinner');
    if (show) {
        spinner.classList.add('show');
    } else {
        spinner.classList.remove('show');
    }
}

function markAsWatched(contentId) {
    if (!confirm('Bu içeriği izlediklerinize eklemek istediğinize emin misiniz?')) {
        return;
    }

    toggleLoading(true);

    fetch('update_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            content_id: contentId,
            status: 'watched'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            alert(data.message || 'Bir hata oluştu. Lütfen tekrar deneyin.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Bir hata oluştu. Lütfen tekrar deneyin.');
    })
    .finally(() => toggleLoading(false));
}

function reactToReview(reviewId, reactionType) {
    if (!confirm('Bu yorumu ' + (reactionType === 'like' ? 'beğenmek' : 'beğenmemek') + ' istediğinize emin misiniz?')) {
        return;
    }

    toggleLoading(true);

    fetch('handle_reaction.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            review_id: reviewId,
            reaction_type: reactionType
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update reaction counts
            const reviewCard = document.querySelector(`[data-review-id="${reviewId}"]`);
            const likesCount = reviewCard.querySelector('.likes-count');
            const dislikesCount = reviewCard.querySelector('.dislikes-count');
            
            likesCount.textContent = data.likes_count;
            dislikesCount.textContent = data.dislikes_count;

            // Update button states
            const likeButton = reviewCard.querySelector('.reaction-button[data-type="like"]');
            const dislikeButton = reviewCard.querySelector('.reaction-button[data-type="dislike"]');

            likeButton.classList.toggle('active', data.user_reaction === 'like');
            dislikeButton.classList.toggle('active', data.user_reaction === 'dislike');
        } else {
            alert(data.message || 'Bir hata oluştu. Lütfen tekrar deneyin.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Bir hata oluştu. Lütfen tekrar deneyin.');
    })
    .finally(() => toggleLoading(false));
}

function toggleReplies(reviewId) {
    const repliesSection = document.getElementById(`replies-${reviewId}`);
    const isHidden = repliesSection.style.display === 'none';

    if (isHidden) {
        toggleLoading(true);
        
        fetch(`get_replies.php?review_id=${reviewId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const repliesHtml = data.replies.map(reply => `
                        <div class="reply-item mt-3" data-reply-id="${reply.id}">
                            <div class="d-flex align-items-start">
                                <img src="${reply.avatar || 'images/default-avatar.png'}" 
                                     alt="${reply.username}" 
                                     class="user-avatar me-2" 
                                     style="width: 30px; height: 30px;">
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between">
                                        <h6 class="mb-1">${reply.username}</h6>
                                        <small class="text-muted">${reply.created_at}</small>
                                    </div>
                                    <p class="mb-2">${reply.reply_text}</p>
                                    <div class="d-flex gap-2">
                                        <button class="reaction-button ${reply.user_reaction === 'like' ? 'active' : ''}"
                                                onclick="reactToReply(${reply.id}, 'like')">
                                            <i class="far fa-thumbs-up"></i>
                                            <span class="likes-count">${reply.likes_count}</span>
                                        </button>
                                        <button class="reaction-button ${reply.user_reaction === 'dislike' ? 'active' : ''}"
                                                onclick="reactToReply(${reply.id}, 'dislike')">
                                            <i class="far fa-thumbs-down"></i>
                                            <span class="dislikes-count">${reply.dislikes_count}</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `).join('');
                    
                    const replyForm = repliesSection.querySelector('.reply-form');
                    const repliesContainer = document.createElement('div');
                    repliesContainer.className = 'replies-container';
                    repliesContainer.innerHTML = repliesHtml;
                    
                    repliesSection.insertBefore(repliesContainer, replyForm);
                    repliesSection.style.display = 'block';
                } else {
                    alert(data.message || 'Yanıtlar yüklenirken bir hata oluştu.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Yanıtlar yüklenirken bir hata oluştu.');
            })
            .finally(() => toggleLoading(false));
    } else {
        const repliesContainer = repliesSection.querySelector('.replies-container');
        if (repliesContainer) {
            repliesContainer.remove();
        }
        repliesSection.style.display = 'none';
    }
}

function submitReply(event, reviewId) {
    event.preventDefault();
    const form = event.target;
    const input = form.querySelector('input');
    const replyText = input.value.trim();

    if (!replyText) {
        alert('Lütfen bir yanıt yazın.');
        return;
    }

    toggleLoading(true);

    fetch('submit_reply.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            review_id: reviewId,
            reply_text: replyText
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Clear input
            input.value = '';
            // Reload replies
            toggleReplies(reviewId);
            toggleReplies(reviewId);
        } else {
            alert(data.message || 'Yanıt gönderilirken bir hata oluştu.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Yanıt gönderilirken bir hata oluştu.');
    })
    .finally(() => toggleLoading(false));
}

function reactToReply(replyId, reactionType) {
    toggleLoading(true);

    fetch('handle_reply_reaction.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            reply_id: replyId,
            reaction_type: reactionType
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const replyItem = document.querySelector(`[data-reply-id="${replyId}"]`);
            const likesCount = replyItem.querySelector('.likes-count');
            const dislikesCount = replyItem.querySelector('.dislikes-count');
            
            likesCount.textContent = data.likes_count;
            dislikesCount.textContent = data.dislikes_count;

            const likeButton = replyItem.querySelector('.reaction-button[onclick*="like"]');
            const dislikeButton = replyItem.querySelector('.reaction-button[onclick*="dislike"]');

            likeButton.classList.toggle('active', data.user_reaction === 'like');
            dislikeButton.classList.toggle('active', data.user_reaction === 'dislike');
        } else {
            alert(data.message || 'Bir hata oluştu. Lütfen tekrar deneyin.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Bir hata oluştu. Lütfen tekrar deneyin.');
    })
    .finally(() => toggleLoading(false));
}

function removeFromList(contentId) {
    if (!confirm('Bu içeriği listenizden çıkarmak istediğinize emin misiniz?')) {
        return;
    }

    toggleLoading(true);

    fetch('remove_from_list.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            content_id: contentId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            alert(data.message || 'Bir hata oluştu. Lütfen tekrar deneyin.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Bir hata oluştu. Lütfen tekrar deneyin.');
    })
    .finally(() => toggleLoading(false));
}

function updateRating(contentId) {
    const rating = prompt('1-5 arasında bir puan girin:', '5');
    
    if (rating === null) return;
    
    const numRating = parseFloat(rating);
    if (isNaN(numRating) || numRating < 1 || numRating > 5) {
        alert('Lütfen 1-5 arasında geçerli bir puan girin.');
        return;
    }

    toggleLoading(true);

    fetch('update_rating.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            content_id: contentId,
            rating: numRating
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            alert(data.message || 'Bir hata oluştu. Lütfen tekrar deneyin.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Bir hata oluştu. Lütfen tekrar deneyin.');
    })
    .finally(() => toggleLoading(false));
}

function deleteReview(reviewId) {
    if (!confirm('Bu yorumu silmek istediğinize emin misiniz?')) {
        return;
    }

    toggleLoading(true);

    fetch('delete_review.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            review_id: reviewId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            alert(data.message || 'Bir hata oluştu. Lütfen tekrar deneyin.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Bir hata oluştu. Lütfen tekrar deneyin.');
    })
    .finally(() => toggleLoading(false));
}

// İçerik ekleme formunu gönderme
$(document).on('submit', '#addNewContent', function(e) {
    e.preventDefault();
    
    $.ajax({
        url: 'add_content.php',
        type: 'POST',
        data: $(this).serialize(),
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Başarılı olduğunda modal içeriğini temizle ve kapat
                $('#addNewContentModal').modal('hide');
                $('#addNewContent')[0].reset();
                
                // Başarı mesajını göster
                showAlert('success', 'İçerik başarıyla eklendi');
                
                // Yeni içeriği seçim listesine ekle
                const option = new Option(response.title, response.content_id);
                $('#existingContent').append(option);
            } else {
                showAlert('error', response.message);
            }
        },
        error: function() {
            showAlert('error', 'Bir hata oluştu');
        }
    });
});

// İzlediklerim listesine ekleme
$(document).on('submit', '#addToWatchedForm', function(e) {
    e.preventDefault();
    
    const formData = $(this).serialize();
    
    $.ajax({
        url: 'add_to_watched.php',
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#addToWatchedModal').modal('hide');
                $('#addToWatchedForm')[0].reset();
                showAlert('success', response.message);
                // Sayfayı yenile
                setTimeout(function() {
                    location.reload();
                }, 1000);
            } else {
                showAlert('error', response.message);
                if (response.message.includes('Oturum')) {
                    // Oturum sorunu varsa login sayfasına yönlendir
                    setTimeout(function() {
                        window.location.href = 'login.php';
                    }, 2000);
                }
            }
        },
        error: function() {
            showAlert('error', 'Bir hata oluştu');
        }
    });
});

// İzleyeceklerim listesine ekleme
$(document).on('submit', '#addToWatchForm', function(e) {
    e.preventDefault();
    
    $.ajax({
        url: 'add_to_watch.php',
        type: 'POST',
        data: $(this).serialize(),
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#addToWatchModal').modal('hide');
                $('#addToWatchForm')[0].reset();
                showAlert('success', 'İçerik izleyeceklerim listesine eklendi');
                // Sayfayı yenile
                location.reload();
            } else {
                showAlert('error', response.message);
            }
        },
        error: function() {
            showAlert('error', 'Bir hata oluştu');
        }
    });
});

// İçeriği listeden kaldırma
function removeFromList(contentId, listType) {
    if (confirm('Bu içeriği listeden kaldırmak istediğinize emin misiniz?')) {
        $.ajax({
            url: 'remove_from_list.php',
            type: 'POST',
            data: { 
                content_id: contentId,
                list_type: listType
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showAlert('success', 'İçerik listeden kaldırıldı');
                    // Sayfayı yenile
                    location.reload();
                } else {
                    showAlert('error', response.message);
                }
            },
            error: function() {
                showAlert('error', 'Bir hata oluştu');
            }
        });
    }
}

// Puan güncelleme
function updateRating(contentId, rating) {
    $.ajax({
        url: 'update_rating.php',
        type: 'POST',
        data: {
            content_id: contentId,
            rating: rating
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showAlert('success', 'Puan güncellendi');
            } else {
                showAlert('error', response.message);
            }
        },
        error: function() {
            showAlert('error', 'Bir hata oluştu');
        }
    });
}

// Bildirim gösterme fonksiyonu
function showAlert(type, message) {
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const alertHtml = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    
    // Alert container'ı kontrol et veya oluştur
    let alertContainer = $('#alertContainer');
    if (alertContainer.length === 0) {
        $('body').prepend('<div id="alertContainer" style="position: fixed; top: 20px; right: 20px; z-index: 9999;"></div>');
        alertContainer = $('#alertContainer');
    }
    
    // Alert'i göster
    const alert = $(alertHtml).appendTo(alertContainer);
    
    // 3 saniye sonra otomatik kapat
    setTimeout(() => {
        alert.alert('close');
    }, 3000);
}
