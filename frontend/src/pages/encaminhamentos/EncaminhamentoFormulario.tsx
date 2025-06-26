import React, { useState, useEffect } from 'react';
import { useNavigate, useParams, useLocation } from 'react-router-dom';
import { useQuery } from 'react-query';
import { useForm, Controller } from 'react-hook-form';
import { yupResolver } from '@hookform/resolvers/yup';
import * as yup from 'yup';
import {
  Box,
  Button,
  Paper,
  Typography,
  Grid,
  TextField,
  FormControl,
  InputLabel,
  MenuItem,
  Select,
  CircularProgress,
  Alert,
  Divider,
  Card,
  CardContent,
  FormHelperText,
  Chip,
  Rating
} from '@mui/material';
import ArrowBackIcon from '@mui/icons-material/ArrowBack';
import SaveIcon from '@mui/icons-material/Save';
import LocalHospitalIcon from '@mui/icons-material/LocalHospital';
import DirectionsIcon from '@mui/icons-material/Directions';
import LocationOnIcon from '@mui/icons-material/LocationOn';
import AmbulanceIcon from '@mui/icons-material/MedicalServices';

import { encaminhamentoService } from '../../services/encaminhamento.service';
import { pacienteService } from '../../services/paciente.service';
import { triagemService } from '../../services/triagem.service';

// Definição explícita do tipo para evitar conflitos de inferência
type FormData = {
  paciente_id: number;
  triagem_id?: number | null;
  ponto_cuidado_id: number;
  veiculo_id?: number | null;
  prioridade: string;
  observacoes?: string | null;
};

// Schema de validação
const schema = yup.object({
  paciente_id: yup.number().required('Selecione um paciente').typeError('Paciente é obrigatório'),
  triagem_id: yup.number().nullable(),
  ponto_cuidado_id: yup.number().required('Selecione um ponto de cuidado').typeError('Ponto de cuidado é obrigatório'),
  veiculo_id: yup.number().nullable(),
  prioridade: yup.string().required('Selecione a prioridade'),
  observacoes: yup.string().nullable()
}).required();

const EncaminhamentoFormulario: React.FC = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const location = useLocation();
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);
  const isEditMode = !!id;

  // Extrair parâmetros da URL
  const urlParams = new URLSearchParams(location.search);
  const pacienteIdParam = urlParams.get('paciente_id');
  const triagemIdParam = urlParams.get('triagem_id');

  // Hook form com validação - usando 'as any' para resolver conflitos de tipagem
  const { control, handleSubmit, reset, watch, setValue, formState: { errors } } = useForm<FormData>({
    resolver: yupResolver(schema) as any,
    defaultValues: {
      paciente_id: pacienteIdParam ? parseInt(pacienteIdParam, 10) : 0,
      triagem_id: triagemIdParam ? parseInt(triagemIdParam, 10) : null,
      ponto_cuidado_id: 0,
      veiculo_id: null,
      prioridade: '',
      observacoes: ''
    }
  });

  const watchedPaciente = watch('paciente_id');
  const watchedTriagem = watch('triagem_id');
  const watchedPrioridade = watch('prioridade');

  // Buscar dados para edição
  const { data: encaminhamentoData, isLoading: isLoadingEncaminhamento } = useQuery(
    ['encaminhamento', id],
    () => encaminhamentoService.obter(id!),
    {
      enabled: isEditMode,
      onSuccess: (data) => {
        const encaminhamento = data.data;
        reset({
          paciente_id: encaminhamento.paciente_id,
          triagem_id: encaminhamento.triagem_id || null,
          ponto_cuidado_id: encaminhamento.ponto_cuidado_id,
          veiculo_id: encaminhamento.veiculo_id || null,
          prioridade: encaminhamento.prioridade,
          observacoes: encaminhamento.observacoes || ''
        });
      },
      onError: () => {
        setError('Erro ao carregar os dados do encaminhamento.');
      }
    }
  );

  // Buscar triagem específica se o ID foi fornecido
  const { data: triagemData } = useQuery(
    ['triagem', watchedTriagem],
    () => {
      // Garantir que watchedTriagem é string ou number
      if (watchedTriagem !== null && watchedTriagem !== undefined) {
        return triagemService.obter(watchedTriagem);
      }
      return Promise.reject('ID de triagem não fornecido');
    },
    { 
      enabled: !!watchedTriagem,
      onSuccess: (data) => {
        const triagem = data.data;
        // Definir prioridade com base no nível de urgência da triagem
        if (triagem && triagem.nivel_urgencia) {
          setValue('prioridade', triagem.nivel_urgencia);
        }
      }
    }
  );

  // Buscar lista de pacientes
  const { data: pacientesData, isLoading: isLoadingPacientes } = useQuery(
    'pacientes-encaminhamento',
    () => pacienteService.listar({ per_page: 100 }),
    {
      enabled: !pacienteIdParam,
      onError: () => {
        setError('Erro ao carregar a lista de pacientes.');
      }
    }
  );

  // Buscar dados do paciente específico se o ID foi fornecido
  const { data: pacienteData } = useQuery(
    ['paciente', watchedPaciente],
    () => pacienteService.obter(watchedPaciente),
    { enabled: !!watchedPaciente && !isLoadingPacientes }
  );

  // Buscar pontos de cuidado disponíveis com base na prioridade
  const { data: pontosCuidadoData, isLoading: isLoadingPontosCuidado } = useQuery(
    ['pontos-cuidado', watchedPrioridade],
    () => encaminhamentoService.listarPontosCuidadoDisponiveis(watchedPrioridade),
    { 
      enabled: !!watchedPrioridade,
      onError: () => {
        setError('Erro ao carregar os pontos de cuidado disponíveis.');
      }
    }
  );

  // Buscar veículos disponíveis
  const { data: veiculosData, isLoading: isLoadingVeiculos } = useQuery(
    'veiculos-disponiveis',
    () => encaminhamentoService.listarVeiculosDisponiveis(),
    {
      onError: () => {
        setError('Erro ao carregar os veículos disponíveis.');
      }
    }
  );

  const pacientes = pacientesData?.data || [];
  const pontosCuidado = pontosCuidadoData?.data || [];
  const veiculos = veiculosData?.data || [];

  // Definimos o handler de submit com tipagem explícita e tratamento para valores nulos
  const onSubmit = async (data: FormData) => {
    setLoading(true);
    setError(null);
    setSuccess(null);

    try {
      // Convertemos os tipos para compatibilidade com a API
      const encaminhamentoData = {
        ...data,
        // Garantimos que campos opcionais sejam number ou undefined, nunca null
        triagem_id: data.triagem_id || undefined,
        veiculo_id: data.veiculo_id || undefined,
        observacoes: data.observacoes || undefined
      };
      
      if (isEditMode) {
        await encaminhamentoService.atualizar(id!, encaminhamentoData as any);
        setSuccess('Encaminhamento atualizado com sucesso!');
      } else {
        await encaminhamentoService.criar(encaminhamentoData as any);
        setSuccess('Encaminhamento criado com sucesso!');
      }

      setTimeout(() => {
        navigate('/encaminhamentos');
      }, 2000);
    } catch (err: any) {
      setError(err.message || 'Ocorreu um erro ao processar o encaminhamento.');
    } finally {
      setLoading(false);
    }
  };

  // Exibir cor diferente com base na ocupação do ponto de cuidado
  const getOcupacaoColor = (ocupacaoAtual: number, capacidadeMaxima: number) => {
    const percentual = (ocupacaoAtual / capacidadeMaxima) * 100;
    if (percentual >= 90) return 'error';
    if (percentual >= 75) return 'warning';
    if (percentual >= 50) return 'info';
    return 'success';
  };

  // Formatar texto da prioridade
  const getPrioridadeLabel = (prioridade: string) => {
    switch (prioridade?.toLowerCase()) {
      case 'critica': return 'Crítica';
      case 'alta': return 'Alta';
      case 'media': return 'Média';
      case 'baixa': return 'Baixa';
      default: return prioridade || 'Não definida';
    }
  };

  // Obter cor da prioridade
  const getPrioridadeColor = (prioridade: string) => {
    switch (prioridade?.toLowerCase()) {
      case 'critica': return 'error';
      case 'alta': return 'warning';
      case 'media': return 'info';
      case 'baixa': return 'success';
      default: return 'default';
    }
  };

  if (isEditMode && isLoadingEncaminhamento) {
    return (
      <Box sx={{ display: 'flex', justifyContent: 'center', mt: 4 }}>
        <CircularProgress />
      </Box>
    );
  }

  return (
    <Box>
      <Box sx={{ mb: 4, display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
        <Typography variant="h4">
          {isEditMode ? 'Editar Encaminhamento' : 'Novo Encaminhamento'}
        </Typography>
        <Button
          variant="outlined"
          startIcon={<ArrowBackIcon />}
          onClick={() => navigate('/encaminhamentos')}
        >
          Voltar
        </Button>
      </Box>

      {error && (
        <Alert severity="error" sx={{ mb: 3 }}>
          {error}
        </Alert>
      )}

      {success && (
        <Alert severity="success" sx={{ mb: 3 }}>
          {success}
        </Alert>
      )}

      <Grid container spacing={3}>
        <Grid sx={{ gridColumn: {xs: 'span 12', md: 'span 8'} }}>
          <Paper sx={{ p: 3 }}>
            <Box component="form" noValidate onSubmit={handleSubmit(onSubmit)}>
              <Grid container spacing={3}>
                <Grid sx={{ gridColumn: 'span 12' }}>
                  <Controller
                    name="paciente_id"
                    control={control}
                    render={({ field }) => (
                      <FormControl fullWidth error={!!errors.paciente_id}>
                        <InputLabel id="paciente-select-label">Paciente</InputLabel>
                        <Select
                          {...field}
                          labelId="paciente-select-label"
                          label="Paciente"
                          disabled={isEditMode || !!pacienteIdParam || isLoadingPacientes}
                        >
                          {isLoadingPacientes ? (
                            <MenuItem value=""><CircularProgress size={20} /> Carregando...</MenuItem>
                          ) : pacienteIdParam && pacienteData?.data ? (
                            <MenuItem value={parseInt(pacienteIdParam, 10)}>
                              {pacienteData.data.nome} ({pacienteData.data.cpf})
                            </MenuItem>
                          ) : pacientes.length === 0 ? (
                            <MenuItem value="">Nenhum paciente encontrado</MenuItem>
                          ) : (
                            pacientes.map((paciente: any) => (
                              <MenuItem key={paciente.id} value={paciente.id}>
                                {paciente.nome} ({paciente.cpf})
                              </MenuItem>
                            ))
                          )}
                        </Select>
                        {errors.paciente_id && (
                          <FormHelperText>{errors.paciente_id.message}</FormHelperText>
                        )}
                      </FormControl>
                    )}
                  />
                </Grid>

                <Grid sx={{ gridColumn: 'span 12' }}>
                  <Controller
                    name="prioridade"
                    control={control}
                    render={({ field }) => (
                      <FormControl fullWidth error={!!errors.prioridade}>
                        <InputLabel id="prioridade-select-label">Prioridade</InputLabel>
                        <Select
                          {...field}
                          labelId="prioridade-select-label"
                          label="Prioridade"
                          disabled={isEditMode || (!!triagemIdParam && !!triagemData)}
                        >
                          <MenuItem value="">Selecione...</MenuItem>
                          <MenuItem value="baixa">Baixa</MenuItem>
                          <MenuItem value="media">Média</MenuItem>
                          <MenuItem value="alta">Alta</MenuItem>
                          <MenuItem value="critica">Crítica</MenuItem>
                        </Select>
                        {errors.prioridade && (
                          <FormHelperText>{errors.prioridade.message}</FormHelperText>
                        )}
                      </FormControl>
                    )}
                  />
                </Grid>

                <Grid sx={{ gridColumn: 'span 12' }}>
                  <Controller
                    name="ponto_cuidado_id"
                    control={control}
                    render={({ field }) => (
                      <FormControl fullWidth error={!!errors.ponto_cuidado_id}>
                        <InputLabel id="ponto-cuidado-select-label">Ponto de Cuidado</InputLabel>
                        <Select
                          {...field}
                          labelId="ponto-cuidado-select-label"
                          label="Ponto de Cuidado"
                          disabled={!watchedPrioridade || isEditMode}
                        >
                          {!watchedPrioridade ? (
                            <MenuItem value="">Selecione a prioridade primeiro</MenuItem>
                          ) : isLoadingPontosCuidado ? (
                            <MenuItem value=""><CircularProgress size={20} /> Carregando...</MenuItem>
                          ) : pontosCuidado.length === 0 ? (
                            <MenuItem value="">Nenhum ponto de cuidado disponível</MenuItem>
                          ) : (
                            pontosCuidado.map((ponto: any) => {
                              const ocupacaoColor = getOcupacaoColor(ponto.ocupacao_atual, ponto.capacidade_maxima);
                              return (
                                <MenuItem key={ponto.id} value={ponto.id}>
                                  {ponto.nome} - {ponto.ocupacao_atual}/{ponto.capacidade_maxima}
                                  <Chip 
                                    size="small" 
                                    color={ocupacaoColor as any}
                                    label={`${Math.round((ponto.ocupacao_atual / ponto.capacidade_maxima) * 100)}%`} 
                                    sx={{ ml: 1 }} 
                                  />
                                </MenuItem>
                              );
                            })
                          )}
                        </Select>
                        {errors.ponto_cuidado_id && (
                          <FormHelperText>{errors.ponto_cuidado_id.message}</FormHelperText>
                        )}
                      </FormControl>
                    )}
                  />
                </Grid>

                <Grid sx={{ gridColumn: 'span 12' }}>
                  <Controller
                    name="veiculo_id"
                    control={control}
                    render={({ field }) => (
                      <FormControl fullWidth>
                        <InputLabel id="veiculo-select-label">Veículo (opcional)</InputLabel>
                        <Select
                          {...field}
                          labelId="veiculo-select-label"
                          label="Veículo (opcional)"
                        >
                          <MenuItem value="">Sem veículo / Transporte próprio</MenuItem>
                          {isLoadingVeiculos ? (
                            <MenuItem value=""><CircularProgress size={20} /> Carregando...</MenuItem>
                          ) : veiculos.length === 0 ? (
                            <MenuItem value="">Nenhum veículo disponível</MenuItem>
                          ) : (
                            veiculos.map((veiculo: any) => (
                              <MenuItem key={veiculo.id} value={veiculo.id}>
                                {veiculo.modelo} - {veiculo.placa}
                              </MenuItem>
                            ))
                          )}
                        </Select>
                      </FormControl>
                    )}
                  />
                </Grid>

                <Grid sx={{ gridColumn: 'span 12' }}>
                  <Controller
                    name="observacoes"
                    control={control}
                    render={({ field }) => (
                      <TextField
                        {...field}
                        label="Observações"
                        multiline
                        rows={4}
                        fullWidth
                        error={!!errors.observacoes}
                        helperText={errors.observacoes?.message}
                      />
                    )}
                  />
                </Grid>

                <Grid sx={{ gridColumn: 'span 12', display: 'flex', justifyContent: 'flex-end' }}>
                  <Button
                    type="submit"
                    variant="contained"
                    startIcon={<SaveIcon />}
                    disabled={loading}
                    sx={{ ml: 2 }}
                  >
                    {loading ? 'Salvando...' : isEditMode ? 'Atualizar' : 'Registrar Encaminhamento'}
                  </Button>
                </Grid>
              </Grid>
            </Box>
          </Paper>
        </Grid>

        <Grid sx={{ gridColumn: {xs: 'span 12', md: 'span 4'} }}>
          <Card sx={{ mb: 3 }}>
            <CardContent>
              <Box sx={{ display: 'flex', alignItems: 'center', mb: 2 }}>
                <DirectionsIcon color="primary" sx={{ mr: 1 }} />
                <Typography variant="h6">
                  Status do Encaminhamento
                </Typography>
              </Box>
              <Divider sx={{ mb: 2 }} />

              {watchedPrioridade ? (
                <Box sx={{ textAlign: 'center', py: 2 }}>
                  <Chip
                    label={getPrioridadeLabel(watchedPrioridade)}
                    color={getPrioridadeColor(watchedPrioridade) as any}
                    sx={{ mb: 2, px: 2, py: 3, fontSize: '1.2rem' }}
                  />
                  
                  <Typography variant="body1" gutterBottom>
                    {watchedPrioridade === 'critica' && 'Necessita atendimento imediato!'}
                    {watchedPrioridade === 'alta' && 'Requer atendimento prioritário'}
                    {watchedPrioridade === 'media' && 'Aguardar disponibilidade'}
                    {watchedPrioridade === 'baixa' && 'Pode aguardar normalização do fluxo'}
                  </Typography>
                </Box>
              ) : (
                <Typography variant="body2" color="text.secondary" sx={{ textAlign: 'center', py: 2 }}>
                  Selecione a prioridade para ver mais informações
                </Typography>
              )}
            </CardContent>
          </Card>

          <Card>
            <CardContent>
              <Box sx={{ display: 'flex', alignItems: 'center', mb: 2 }}>
                <LocationOnIcon color="primary" sx={{ mr: 1 }} />
                <Typography variant="h6">
                  Informações Adicionais
                </Typography>
              </Box>
              <Divider sx={{ mb: 2 }} />

              {triagemIdParam && triagemData?.data ? (
                <Box sx={{ mb: 3 }}>
                  <Typography variant="subtitle1" gutterBottom>
                    Triagem Associada
                  </Typography>
                  <Chip
                    label={getPrioridadeLabel(triagemData.data.nivel_urgencia)}
                    color={getPrioridadeColor(triagemData.data.nivel_urgencia) as any}
                    size="small"
                    sx={{ mb: 1 }}
                  />
                  <Typography variant="body2" color="text.secondary">
                    Classificação automática com base nos sintomas registrados
                  </Typography>
                </Box>
              ) : (
                <Typography variant="body2" color="text.secondary" gutterBottom>
                  Nenhuma triagem associada
                </Typography>
              )}

              {pacienteData?.data && (
                <Box>
                  <Typography variant="subtitle1" gutterBottom sx={{ mt: 2 }}>
                    Dados do Paciente
                  </Typography>
                  <Typography variant="body2">
                    <strong>Nome:</strong> {pacienteData.data.nome}
                  </Typography>
                  <Typography variant="body2">
                    <strong>CPF:</strong> {pacienteData.data.cpf}
                  </Typography>
                  <Typography variant="body2">
                    <strong>Telefone:</strong> {pacienteData.data.telefone}
                  </Typography>
                </Box>
              )}
            </CardContent>
          </Card>
        </Grid>
      </Grid>
    </Box>
  );
};

export default EncaminhamentoFormulario;
