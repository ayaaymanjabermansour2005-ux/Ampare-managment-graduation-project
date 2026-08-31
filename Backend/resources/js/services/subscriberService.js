import http from "./http";

export default {
  updateBeneficiaryType(subscriberId, beneficiaryType) {
    return http.patch(`/subscribers/${subscriberId}/beneficiary-type`, {
      beneficiary_type: beneficiaryType,
    });
  },
};
