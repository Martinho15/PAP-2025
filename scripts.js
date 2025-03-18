document.addEventListener('DOMContentLoaded', () => {
    // Dados mockados dos laboratórios
    const laboratorios = [
        {
            id: 1,
            nome: "Laboratório Nacional",
            nif: "123456789",
            endereco: "Rua Principal, 123, Luanda",
            telefone: "+244 923 456 789",
            email: "lab.nacional@email.com",
            status: "active"
        },
        {
            id: 2,
            nome: "Laboratório Central",
            nif: "987654321",
            endereco: "Av. Central, 456, Luanda",
            telefone: "+244 923 789 456",
            email: "lab.central@email.com",
            status: "active"
        }
    ];

    // Renderizar laboratórios
    function renderizarLaboratorios() {
        const labGrid = document.querySelector('.lab-grid');
        labGrid.innerHTML = laboratorios.map(lab => `
            <div class="lab-card" data-id="${lab.id}">
                <div class="lab-header">
                    <h3>${lab.nome}</h3>
                    <span class="status-badge ${lab.status}">${lab.status === 'active' ? 'Ativo' : 'Inativo'}</span>
                </div>
                <div class="lab-info">
                    <p><i class="fas fa-id-card"></i> NIF: ${lab.nif}</p>
                    <p><i class="fas fa-phone"></i> ${lab.telefone}</p>
                    <p><i class="fas fa-envelope"></i> ${lab.email}</p>
                    <p><i class="fas fa-map-marker-alt"></i> ${lab.endereco}</p>
                </div>
                <div class="lab-actions">
                    <button class="btn-icon" onclick="editarLaboratorio(${lab.id})" title="Editar">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn-icon" onclick="excluirLaboratorio(${lab.id})" title="Excluir">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `).join('');
    }

    // Controles do modal
    const modal = document.getElementById('lab-modal');
    const addLabBtn = document.getElementById('add-lab-btn');
    const closeModal = document.getElementById('close-modal');
    const cancelModal = document.getElementById('cancel-modal');
    const saveLabBtn = document.getElementById('save-lab');
    const labForm = document.getElementById('lab-form');
    let editandoId = null;

    // Abrir modal para adicionar
    addLabBtn.addEventListener('click', () => {
        editandoId = null;
        labForm.reset();
        document.getElementById('modal-title').textContent = 'Adicionar Laboratório';
        modal.style.display = 'flex';
    });

    // Fechar modal
    [closeModal, cancelModal].forEach(btn => {
        btn.addEventListener('click', () => {
            modal.style.display = 'none';
        });
    });

    // Salvar laboratório
    saveLabBtn.addEventListener('click', (e) => {
        e.preventDefault();
        
        if (labForm.checkValidity()) {
            const labData = {
                id: editandoId || laboratorios.length + 1,
                nome: document.getElementById('lab-name').value,
                nif: document.getElementById('lab-nif').value,
                endereco: document.getElementById('lab-address').value,
                telefone: document.getElementById('lab-phone').value,
                email: document.getElementById('lab-email').value,
                status: document.getElementById('lab-status').value
            };

            if (editandoId) {
                const index = laboratorios.findIndex(l => l.id === editandoId);
                if (index !== -1) {
                    laboratorios[index] = labData;
                }
            } else {
                laboratorios.push(labData);
            }

            renderizarLaboratorios();
            modal.style.display = 'none';
            labForm.reset();
        } else {
            labForm.reportValidity();
        }
    });

    // Pesquisar laboratórios
    const searchInput = document.querySelector('.search-lab input');
    searchInput.addEventListener('input', (e) => {
        const termo = e.target.value.toLowerCase();
        const labCards = document.querySelectorAll('.lab-card');
        
        labCards.forEach(card => {
            const nome = card.querySelector('h3').textContent.toLowerCase();
            const nif = card.querySelector('.fa-id-card').parentElement.textContent.toLowerCase();
            
            if (nome.includes(termo) || nif.includes(termo)) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });
    });

    // Funções globais para edição e exclusão
    window.editarLaboratorio = (id) => {
        const lab = laboratorios.find(l => l.id === id);
        if (lab) {
            editandoId = id;
            document.getElementById('modal-title').textContent = 'Editar Laboratório';
            document.getElementById('lab-name').value = lab.nome;
            document.getElementById('lab-nif').value = lab.nif;
            document.getElementById('lab-address').value = lab.endereco;
            document.getElementById('lab-phone').value = lab.telefone;
            document.getElementById('lab-email').value = lab.email;
            document.getElementById('lab-status').value = lab.status;
            
            modal.style.display = 'flex';
        }
    };

    window.excluirLaboratorio = (id) => {
        if (confirm('Tem certeza que deseja excluir este laboratório?')) {
            const index = laboratorios.findIndex(l => l.id === id);
            if (index !== -1) {
                laboratorios.splice(index, 1);
                renderizarLaboratorios();
            }
        }
    };

    // Inicializar a página
    renderizarLaboratorios();
});