/**
 * Reviews List Component - ES6+ Module
 * AJAX функциональность для компонента отзывов
 */
// if (typeof ReviewsComponent === 'undefined') {
     class ReviewsComponent {
        constructor(containerId) {
            this.container = document.getElementById(containerId);
            if (!this.container) {
                console.error(`Container #${containerId} not found`);
                return;
            }

            this.init();
        }

        /**
         * Инициализация компонента
         */
        init() {
            this.bindEvents();
            console.log('ReviewsComponent initialized');
        }

        /**
         * Навешиваем обработчики событий
         */
        bindEvents() {
            const form = this.container.querySelector('#review-form');
            if (form) {
                form.addEventListener('submit', this.handleFormSubmit.bind(this));
            }

            // Валидация в реальном времени
            this.setupLiveValidation();
        }

        /**
         * Оптимистичное обновление
         */
        async handleFormSubmit(event) {
            event.preventDefault();
            
            const form = event.target;
            const formData = new FormData(form);
            
            if (!this.validateForm(form)) return;

            // 1. СОЗДАЕМ ОПТИМИСТИЧНЫЙ ОТЗЫВ (immediately)
            const optimisticReview = this.createOptimisticReview(formData);
            const optimisticElement = this.createReviewElement(optimisticReview);
            this.addNewReviewToDOM(optimisticElement, true); // true = isOptimistic
            
            // 2. ОБНОВЛЯЕМ СЧЕТЧИК
            this.updateReviewsCount(1);
            
            // 3. БЛОКИРУЕМ ФОРМУ
            this.setFormLoadingState(form, true);

            try {
                // 4. ОТПРАВЛЯЕМ НА СЕРВЕР
                const result = await this.submitReview(formData);
                
                // 5. ЗАМЕНЯЕМ ОПТИМИСТИЧНЫЙ ОТЗЫВ РЕАЛЬНЫМИ ДАННЫМИ
                this.replaceWithRealReview(optimisticElement, result.data.review);
                this.showNotification('Отзыв успешно добавлен!', 'success');
                
            } catch (error) {
                // 6. ОБРАБОТКА ОШИБОК - откатываем оптимистичное обновление
                this.rollbackOptimisticUpdate(optimisticElement);
                this.handleError(error);
            } finally {
                this.setFormLoadingState(form, false);
                form.reset();
            }
        }
        
        /**
         * Создание оптимистичного отзыва (до ответа сервера)
         */
        createOptimisticReview(formData) {
            return {
                ID: 'temp-' + Date.now(), // временный ID
                AUTHOR: formData.get('author'),
                TEXT: formData.get('text'),
                RATING: parseInt(formData.get('rating')),
                FORMATTED_DATE: 'Только что',
                IS_OPTIMISTIC: true // флаг для стилей
            };
        } 
        
        /**
         * Добавление нового отзыва в DOM
         */
        addNewReviewToDOM(reviewElement, isOptimistic = false) {
            const reviewsContainer = this.container.querySelector('.reviews-container');
            const noReviewsElement = this.container.querySelector('.no-reviews');
            
            // Убираем сообщение "нет отзывов", если оно есть
            if (noReviewsElement) {
                noReviewsElement.remove();
            }
            
            // Добавляем специальный класс для оптимистичных отзывов
            if (isOptimistic) {
                reviewElement.classList.add('review-optimistic');
            }
            
            // Добавляем в начало списка (самый новый сверху)
            if (reviewsContainer.firstChild) {
                reviewsContainer.insertBefore(reviewElement, reviewsContainer.firstChild);
            } else {
                reviewsContainer.appendChild(reviewElement);
            }
            
            // Анимация появления
            this.animateNewReview(reviewElement);
            
            return reviewElement;
        }      
        
        /**
         * Замена оптимистичного отзыва реальными данными
         */
        replaceWithRealReview(optimisticElement, realReviewData) {
            const realElement = this.createReviewElement(realReviewData);
            
            // Плавная замена
            optimisticElement.style.opacity = '0.5';
            optimisticElement.style.transform = 'scale(0.95)';
            
            setTimeout(() => {
                optimisticElement.replaceWith(realElement);
                this.animateNewReview(realElement);
            }, 300);
        }

        /**
         * Откат оптимистичного обновления при ошибке
         */
        rollbackOptimisticUpdate(optimisticElement) {
            // Анимация исчезновения
            optimisticElement.style.transition = 'all 0.3s ease';
            optimisticElement.style.opacity = '0';
            optimisticElement.style.transform = 'translateX(-100%)';
            
            setTimeout(() => {
                optimisticElement.remove();
                
                // Проверяем, не остался ли список пустым
                const reviewsContainer = this.container.querySelector('.reviews-container');
                if (!reviewsContainer.children.length) {
                    reviewsContainer.innerHTML = `
                        <div class="no-reviews">
                            <p>Пока нет отзывов. Будьте первым!</p>
                        </div>
                    `;
                }
            }, 300);
            
            // Откатываем счетчик
            this.updateReviewsCount(-1);
        }             

        /**
         * Валидация формы
         */
        validateForm(form) {
            let isValid = true;
            const fields = ['author', 'rating', 'text'];

            // Сбрасываем предыдущие ошибки
            this.clearErrors();

            fields.forEach(fieldName => {
                const field = form.querySelector(`[name="${fieldName}"]`);
                const errorElement = form.querySelector(`[data-field="${fieldName}"]`);

                if (!field.value.trim()) {
                    this.showFieldError(field, errorElement, 'Это поле обязательно для заполнения');
                    isValid = false;
                } else if (fieldName === 'text' && field.value.trim().length < 10) {
                    this.showFieldError(field, errorElement, 'Текст отзыва должен быть не менее 10 символов');
                    isValid = false;
                } else if (fieldName === 'author' && field.value.trim().length > 100) {
                    this.showFieldError(field, errorElement, 'Имя не должно превышать 100 символов');
                    isValid = false;
                } else {
                    this.hideFieldError(field, errorElement);
                }
            });

            return isValid;
        }

        /**
         * Валидация в реальном времени
         */
        setupLiveValidation() {
            const form = this.container.querySelector('#review-form');
            if (!form) return;

            const fields = form.querySelectorAll('input, select, textarea');
            
            fields.forEach(field => {
                field.addEventListener('blur', () => {
                    this.validateField(field);
                });

                field.addEventListener('input', () => {
                    // Сбрасываем ошибку при вводе
                    const errorElement = form.querySelector(`[data-field="${field.name}"]`);
                    this.hideFieldError(field, errorElement);
                });
            });
        }

        /**
         * Валидация отдельного поля
         */
        validateField(field) {
            const errorElement = this.container.querySelector(`[data-field="${field.name}"]`);
            
            if (!field.value.trim()) {
                this.showFieldError(field, errorElement, 'Это поле обязательно для заполнения');
                return false;
            }

            if (field.name === 'text' && field.value.trim().length < 10) {
                this.showFieldError(field, errorElement, 'Текст отзыва должен быть не менее 10 символов');
                return false;
            }

            if (field.name === 'author' && field.value.trim().length > 100) {
                this.showFieldError(field, errorElement, 'Имя не должно превышать 100 символов');
                return false;
            }

            this.hideFieldError(field, errorElement);
            return true;
        }

        /**
         * Показать ошибку поля
         */
        showFieldError(field, errorElement, message) {
            field.classList.add('error');
            if (errorElement) {
                errorElement.textContent = message;
                errorElement.classList.add('show');
            }
        }

        /**
         * Скрыть ошибку поля
         */
        hideFieldError(field, errorElement) {
            field.classList.remove('error');
            if (errorElement) {
                errorElement.classList.remove('show');
            }
        }

        /**
         * Очистить все ошибки
         */
        clearErrors() {
            const errorElements = this.container.querySelectorAll('.error-message');
            const errorFields = this.container.querySelectorAll('.error');

            errorElements.forEach(element => {
                element.classList.remove('show');
            });

            errorFields.forEach(field => {
                field.classList.remove('error');
            });
        }

        /**
         * Отправка отзыва через AJAX
         */
        async submitReview(formData) {
            console.log('🔄 Starting submit (with artificial delay)...');
    
            // ИСКУССТВЕННАЯ ЗАДЕРЖКА 3 СЕКУНДЫ
            await new Promise(resolve => setTimeout(resolve, 3000));
            
            const ajaxUrl = this.container.getAttribute('data-ajax-url');
            const response = await fetch(ajaxUrl, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: formData
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();

            if (!result.success) {
                throw new Error(result.message || 'Произошла ошибка при отправке');
            }

            return result;
        }

        /**
         * Обработка успешной отправки
         */
        handleSuccess(form) {
            // Очищаем форму
            form.reset();
            
            // Показываем уведомление
            this.showNotification('Отзыв успешно добавлен!', 'success');
            
            // Обновляем список отзывов
            this.refreshReviewsList();
            
            // Обновляем счетчик отзывов
            this.updateReviewsCount(1);
        }

        /**
         * Обработка ошибки
         */
        handleError(error) {
            console.error('Error submitting review:', error);
            this.showNotification(
                error.message || 'Произошла ошибка при отправке отзыва', 
                'error'
            );
        }

        /**
         * Обновление списка отзывов
         */
        async refreshReviewsList() {
            try {
                // Можно сделать AJAX запрос для получения обновленного списка
                // или просто перезагрузить страницу (проще для тестового)
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } catch (error) {
                console.error('Error refreshing reviews:', error);
            }
        }

        /**
         * Обновление счетчика отзывов
         */
        updateReviewsCount(increment = 1) {
            const countElement = this.container.querySelector('.reviews-count');
            if (countElement) {
                const currentCount = parseInt(countElement.textContent) || 0;
                countElement.textContent = currentCount + increment;
            }
        }

        /**
         * Установка состояния загрузки формы
         */
        setFormLoadingState(form, isLoading) {
            const submitBtn = form.querySelector('.submit-btn');
            const btnText = submitBtn.querySelector('.btn-text');
            const btnLoading = submitBtn.querySelector('.btn-loading');

            if (isLoading) {
                submitBtn.disabled = true;
                btnText.style.display = 'none';
                btnLoading.style.display = 'inline';
                form.classList.add('loading');
            } else {
                submitBtn.disabled = false;
                btnText.style.display = 'inline';
                btnLoading.style.display = 'none';
                form.classList.remove('loading');
            }
        }

        /**
         * Показать уведомление
         */
        showNotification(message, type = 'info') {
            // Создаем или находим контейнер для уведомлений
            let notificationContainer = document.getElementById('review-notification');
            
            if (!notificationContainer) {
                notificationContainer = document.createElement('div');
                notificationContainer.id = 'review-notification';
                notificationContainer.className = 'notification';
                document.body.appendChild(notificationContainer);
            }

            // Устанавливаем сообщение и тип
            notificationContainer.textContent = message;
            notificationContainer.className = `notification ${type}`;
            notificationContainer.style.display = 'block';

            // Анимация появления
            setTimeout(() => {
                notificationContainer.classList.remove('hidden');
            }, 100);

            // Автоматическое скрытие
            setTimeout(() => {
                this.hideNotification(notificationContainer);
            }, 5000);
        }

        /**
         * Скрыть уведомление
         */
        hideNotification(notificationElement) {
            notificationElement.classList.add('hidden');
            
            setTimeout(() => {
                notificationElement.style.display = 'none';
                notificationElement.classList.remove('hidden');
            }, 300);
        }

        /**
         * Создание HTML для отзыва
         */
        createReviewElement(reviewData) {
            const reviewElement = document.createElement('div');
            reviewElement.className = 'review-card';
            reviewElement.setAttribute('data-review-id', reviewData.ID);

            // Создаем звезды рейтинга
            const starsHtml = Array.from({ length: 5 }, (_, i) => 
                `<span class="star ${i < reviewData.RATING ? 'active' : ''}">★</span>`
            ).join('');

            reviewElement.innerHTML = `
                <div class="review-header">
                    <div class="review-author">${this.escapeHtml(reviewData.AUTHOR)}</div>
                    <div class="review-date">${reviewData.FORMATTED_DATE}</div>
                </div>
                <div class="review-rating">
                    <div class="stars">${starsHtml}</div>
                    <span class="rating-value">${reviewData.RATING}/5</span>
                </div>
                <div class="review-text">${this.nl2br(this.escapeHtml(reviewData.TEXT))}</div>
            `;

            return reviewElement;
        }

        /**
         * Замена переносов строк на <br>
         */
        nl2br(str) {
            if (typeof str !== 'string') return str;
            return str.replace(/\n/g, '<br>');
        }

        /**
         * Экранирование HTML
         */
        escapeHtml(unsafe) {
            if (typeof unsafe !== 'string') return unsafe;
            return unsafe
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }
        
        
         /**
         * Анимация появления нового отзыва
         */
        animateNewReview(element) {
            // Убедимся, что элемент видим
            element.style.opacity = '0';
            element.style.transform = 'translateY(-20px)';
            element.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
            
            // Запускаем анимацию в следующем фрейме
            requestAnimationFrame(() => {
                element.style.opacity = '1';
                element.style.transform = 'translateY(0)';
            });
            
            // Убираем inline стили после анимации (опционально)
            setTimeout(() => {
                element.style.transition = '';
            }, 300);
        }

        /**
         * Анимация удаления отзыва
         */
        animateRemoveReview(element, callback) {
            element.style.transition = 'all 0.3s ease';
            element.style.opacity = '0';
            element.style.transform = 'translateX(-100%)';
            element.style.maxHeight = '0';
            element.style.marginBottom = '0';
            element.style.paddingTop = '0';
            element.style.paddingBottom = '0';
            element.style.overflow = 'hidden';
            
            setTimeout(() => {
                if (callback) callback();
            }, 300);
        }       
        
    }
// } 


// Инициализация компонента при загрузке DOM
document.addEventListener('DOMContentLoaded', () => {
    const reviewContainers = document.querySelectorAll('[id^="reviews-component-"]');
    
    reviewContainers.forEach(container => {
        new ReviewsComponent(container.id);
    });
});

// Экспорт для возможного использования извне
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ReviewsComponent;
}
