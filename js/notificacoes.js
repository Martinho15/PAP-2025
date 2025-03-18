/**
 * Sistema de Gestão de Armazéns de Medicamentos Angola
 * Módulo de Notificações
 */

class NotificacaoManager {
    constructor() {
        this.container = document.querySelector('.notifications-content');
        this.badge = document.querySelector('.badge');
        this.markAllReadBtn = document.querySelector('.mark-all-read');
        this.notificationsBtn = document.getElementById('notificationsBtn');
        this.notificationsDropdown = document.querySelector('.notifications-dropdown');
        
        this.init();
    }
    
    init() {
        // Carregar notificações ao iniciar
        this.carregarNotificacoes();
        
        // Configurar eventos
        this.configurarEventos();
        
        // Atualizar notificações a cada 5 minutos
        setInterval(() => this.carregarNotificacoes(), 5 * 60 * 1000);
    }
    
    configurarEventos() {
        // Abrir/fechar dropdown de notificações
        if (this.notificationsBtn) {
            this.notificationsBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.notificationsDropdown.classList.toggle('show');
                
                // Atualiza as notificações quando abrir o dropdown
                if (this.notificationsDropdown.classList.contains('show')) {
                    this.carregarNotificacoes();
                }
            });
        }
        
        // Fechar dropdown ao clicar fora
        document.addEventListener('click', (e) => {
            if (this.notificationsDropdown && 
                !this.notificationsDropdown.contains(e.target) && 
                !this.notificationsBtn.contains(e.target)) {
                this.notificationsDropdown.classList.remove('show');
            }
        });
        
        // Marcar todas como lidas
        if (this.markAllReadBtn) {
            this.markAllReadBtn.addEventListener('click', () => {
                this.marcarTodasComoLidas();
            });
        }
        
        // Marcar individual como lida ou clicar no link
        if (this.container) {
            this.container.addEventListener('click', (e) => {
                const notificationItem = e.target.closest('.notification-item');
                if (notificationItem) {
                    const id = notificationItem.getAttribute('data-id');
                    
                    // Se clicou no link, abrir o link
                    const link = e.target.closest('.notification-link');
                    if (link) {
                        e.preventDefault();
                        const url = link.getAttribute('href');
                        if (url) {
                            window.location.href = url;
                        }
                    }
                    
                    // Marcar como lida
                    if (id && notificationItem.classList.contains('unread')) {
                        this.marcarComoLida(id);
                    }
                }
            });
        }
    }
    
    carregarNotificacoes() {
        fetch('notificacoes_crud.php?acao=listar&limite=10')
            .then(response => response.json())
            .then(data => {
                this.atualizarNotificacoes(data.notificacoes);
                this.atualizarContador(data.total_nao_lidas);
            })
            .catch(error => {
                console.error('Erro ao carregar notificações:', error);
            });
    }
    
    atualizarNotificacoes(notificacoes) {
        if (!this.container) return;
        
        this.container.innerHTML = '';
        
        if (notificacoes.length === 0) {
            this.container.innerHTML = '<div class="empty-state">Não há notificações.</div>';
            return;
        }
        
        notificacoes.forEach(notificacao => {
            const item = document.createElement('div');
            item.className = 'notification-item';
            item.setAttribute('data-id', notificacao.id_notificacao);
            
            if (notificacao.lida == 0) {
                item.classList.add('unread');
            }
            
            // Definir ícone com base no tipo
            let iconClass = 'fas fa-info-circle';
            let typeClass = 'info';
            
            switch (notificacao.tipo) {
                case 'danger':
                    iconClass = 'fas fa-exclamation-circle';
                    typeClass = 'danger';
                    break;
                case 'warning':
                    iconClass = 'fas fa-exclamation-triangle';
                    typeClass = 'warning';
                    break;
                case 'success':
                    iconClass = 'fas fa-check-circle';
                    typeClass = 'success';
                    break;
            }
            
            // Formatar data
            const data = new Date(notificacao.data_criacao);
            const dataFormatada = data.toLocaleDateString('pt-PT') + ' ' + 
                                 data.toLocaleTimeString('pt-PT', {hour: '2-digit', minute:'2-digit'});
            
            // Criar conteúdo HTML
            const conteudo = `
                <div class="notification-icon ${typeClass}">
                    <i class="${iconClass}"></i>
                    ${notificacao.lida == 0 ? '<span class="notification-dot"></span>' : ''}
                </div>
                <div class="notification-info">
                    <div class="notification-title">${notificacao.titulo}</div>
                    <div class="notification-message">${notificacao.mensagem}</div>
                    <div class="notification-time">${dataFormatada}</div>
                    ${notificacao.link ? `<a href="${notificacao.link}" class="notification-link">Ver detalhes</a>` : ''}
                </div>
            `;
            
            item.innerHTML = conteudo;
            this.container.appendChild(item);
        });
    }
    
    atualizarContador(total) {
        if (!this.badge) return;
        
        this.badge.textContent = total;
        this.badge.style.display = total > 0 ? 'inline' : 'none';
    }
    
    marcarComoLida(id) {
        fetch('notificacoes_crud.php?acao=marcar_lida', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: id })
        })
        .then(response => response.json())
        .then(data => {
            if (data.sucesso) {
                // Atualizar interface
                const item = document.querySelector(`.notification-item[data-id="${id}"]`);
                if (item) {
                    item.classList.remove('unread');
                    const dot = item.querySelector('.notification-dot');
                    if (dot) {
                        dot.remove();
                    }
                }
                
                // Atualizar contador
                const currentCount = parseInt(this.badge.textContent);
                this.badge.textContent = Math.max(0, currentCount - 1);
                this.badge.style.display = this.badge.textContent > 0 ? 'inline' : 'none';
            }
        })
        .catch(error => {
            console.error('Erro ao marcar notificação como lida:', error);
        });
    }
    
    marcarTodasComoLidas() {
        fetch('notificacoes_crud.php?acao=marcar_todas_lidas', {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            if (data.sucesso) {
                // Atualizar interface
                const items = document.querySelectorAll('.notification-item.unread');
                items.forEach(item => {
                    item.classList.remove('unread');
                    const dot = item.querySelector('.notification-dot');
                    if (dot) {
                        dot.remove();
                    }
                });
                
                // Zerar contador
                this.badge.textContent = '0';
                this.badge.style.display = 'none';
            }
        })
        .catch(error => {
            console.error('Erro ao marcar todas notificações como lidas:', error);
        });
    }
    
    excluirNotificacao(id) {
        fetch('notificacoes_crud.php?acao=excluir', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: id })
        })
        .then(response => response.json())
        .then(data => {
            if (data.sucesso) {
                // Atualizar interface
                const item = document.querySelector(`.notification-item[data-id="${id}"]`);
                if (item) {
                    item.remove();
                    
                    // Verificar se era não lida e atualizar contador
                    if (item.classList.contains('unread')) {
                        const currentCount = parseInt(this.badge.textContent);
                        this.badge.textContent = Math.max(0, currentCount - 1);
                        this.badge.style.display = this.badge.textContent > 0 ? 'inline' : 'none';
                    }
                    
                    // Se não há mais notificações, mostrar mensagem
                    if (this.container.children.length === 0) {
                        this.container.innerHTML = '<div class="empty-state">Não há notificações.</div>';
                    }
                }
            }
        })
        .catch(error => {
            console.error('Erro ao excluir notificação:', error);
        });
    }
    
    verificarEstoquesBaixos() {
        fetch('notificacoes_crud.php?acao=verificar_estoques')
            .then(response => response.json())
            .then(data => {
                if (data.sucesso && data.notificacoes_criadas > 0) {
                    this.carregarNotificacoes();
                }
            })
            .catch(error => {
                console.error('Erro ao verificar estoques baixos:', error);
            });
    }
}

// Inicializar o gerenciador de notificações quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', function() {
    window.notificacaoManager = new NotificacaoManager();
    
    // Verificar estoques baixos ao carregar a página
    setTimeout(() => {
        if (window.notificacaoManager) {
            window.notificacaoManager.verificarEstoquesBaixos();
        }
    }, 2000);
});
